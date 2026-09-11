(function () {
	'use strict';

	var config = window.sbtyAdmin || {};
	var wrap = document.querySelector('.sefm-wrap');
	var toolbar = document.querySelector('.sefm-workspace-save');
	if (!wrap || !toolbar || !config.ajaxUrl || !window.fetch || !window.Map || !window.FormData || !window.DOMParser || !window.AbortController) {
		return;
	}

	var pages = new Map();
	var saving = false;
	var button = document.getElementById('sbty-save-all');
	var status = document.getElementById('sbty-save-status');

	function pageKey(url) {
		var parsed = new URL(url, window.location.href);
		var tab = parsed.searchParams.get('tab') || 'typography';
		return tab + ':' + (tab === 'typography' ? parsed.searchParams.get('section') || 'global' : '');
	}

	function dirtyPages() {
		return Array.from(pages.values()).filter(function (page) { return page.dirty; });
	}

	function notice(message, error) {
		status.textContent = message || '';
		status.classList.toggle('is-error', !!error);
	}

	function updateStatus(message) {
		var dirty = dirtyPages().length > 0;
		button.disabled = saving || !dirty;
		toolbar.setAttribute('aria-busy', saving ? 'true' : 'false');
		notice(saving ? config.saving : dirty ? config.unsaved : message);
	}

	function remember(url, main) {
		var key = pageKey(url);
		if (!pages.has(key)) {
			pages.set(key, {main: main, url: url, dirty: false, revision: 0});
		}
		// Native submit remains available if JavaScript enhancement is unavailable.
		main.querySelectorAll('[data-sbty-settings-form]').forEach(function (form) { form.noValidate = true; });
		return pages.get(key);
	}

	function changed(target) {
		var form = target.closest('[data-sbty-settings-form]');
		if (!form) {
			return;
		}
		pages.forEach(function (page) {
			if (page.main.contains(form)) {
				page.dirty = true;
				page.revision++;
			}
		});
		updateStatus();
	}

	function collectForm(form, payload) {
		var custom = new Map();
		new FormData(form).forEach(function (value, name) {
			var top = name.match(/^sbty_settings\[(priority_mode|custom_rules_present)\]$/);
			if (top) {
				payload[top[1]] = String(value);
				return;
			}
			var field = name.match(/^sbty_settings\[(assignments|custom_rules)\]\[([^\]]+)\]\[([a-z_]+)\](\[\])?$/);
			if (!field) {
				return;
			}
			var rule;
			if (field[1] === 'custom_rules') {
				if (!custom.has(field[2])) { custom.set(field[2], Object.create(null)); }
				rule = custom.get(field[2]);
			} else {
				if (!payload.assignments) { payload.assignments = Object.create(null); }
				if (!payload.assignments[field[2]]) { payload.assignments[field[2]] = Object.create(null); }
				rule = payload.assignments[field[2]];
			}
			if (field[4]) {
				if (!rule[field[3]]) { rule[field[3]] = []; }
				rule[field[3]].push(String(value));
			} else {
				rule[field[3]] = String(value);
			}
		});
		if (form.querySelector('[name="sbty_settings[custom_rules_present]"]')) {
			payload.custom_rules = Array.from(custom.values());
		}
	}

	function revealInvalid(page, control) {
		window.dispatchEvent(new CustomEvent('sbty-workspace-navigate', {detail: page.url}));
		var layer = control.closest('.sefm-layer-panel');
		if (layer) {
			var fields = layer.closest('.sefm-assignment-fields');
			var tab = fields.querySelector('[data-sefm-layer="' + layer.getAttribute('data-sefm-layer-panel') + '"]');
			if (tab) { tab.click(); }
		}
		var details = control.closest('details');
		if (details) { details.open = true; }
		control.focus();
		control.reportValidity();
		notice(config.invalidField, true);
	}

	function saveAll() {
		var dirty = dirtyPages();
		if (saving || !dirty.length) {
			return;
		}
		var payload = Object.create(null);
		var snapshots = [];
		for (var i = 0; i < dirty.length; i++) {
			var forms = dirty[i].main.querySelectorAll('[data-sbty-settings-form]');
			for (var j = 0; j < forms.length; j++) {
				var invalid = forms[j].querySelector('input:invalid, select:invalid, textarea:invalid');
				if (invalid) {
					revealInvalid(dirty[i], invalid);
					return;
				}
				collectForm(forms[j], payload);
			}
			snapshots.push({page: dirty[i], revision: dirty[i].revision});
		}

		var body = new FormData();
		body.append('action', 'sbty_save_all');
		body.append('nonce', config.saveNonce);
		body.append('payload', JSON.stringify(payload));
		saving = true;
		updateStatus();
		fetch(config.ajaxUrl, {method: 'POST', credentials: 'same-origin', body: body})
			.then(function (response) { return response.json(); })
			.then(function (result) {
				if (!result || !result.success) {
					throw new Error(result && result.data && result.data.message || config.saveError);
				}
				if (result.data.nonce) { config.saveNonce = result.data.nonce; }
				snapshots.forEach(function (snapshot) {
					// Edits made while the request was in flight still need saving.
					if (snapshot.page.revision === snapshot.revision) { snapshot.page.dirty = false; }
				});
				pages.forEach(function (page) {
					var preview = page.main.querySelector('#sefm-css-preview code');
					var copy = page.main.querySelector('#sefm-copy-css');
					if (preview) { preview.textContent = result.data.css || config.emptyCss; }
					if (copy) { copy.disabled = !result.data.css; }
				});
				saving = false;
				updateStatus(config.saved);
			}).catch(function (error) {
				saving = false;
				updateStatus();
				notice(error.message || config.saveError, true);
			});
	}

	window.sbtyWorkspace = {
		get: function (url) { return pages.get(pageKey(url)); },
		remember: remember,
		changed: changed,
		notice: notice
	};
	remember(window.location.href, document.querySelector('.sefm-main'));
	wrap.classList.add('has-workspace');
	toolbar.hidden = false;
	button.addEventListener('click', saveAll);
	['input', 'change'].forEach(function (type) {
		document.addEventListener(type, function (event) {
			if (event.target.name && event.target.name.indexOf('sbty_settings[') === 0) {
				changed(event.target);
			}
		});
	});
	document.addEventListener('submit', function (event) {
		if (event.target.matches('[data-sbty-settings-form]')) {
			event.preventDefault();
			saveAll();
		}
	});
	window.addEventListener('beforeunload', function (event) {
		if (saving || dirtyPages().length) {
			event.preventDefault();
			event.returnValue = '';
		}
	});
}());
