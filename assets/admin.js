(function () {
	'use strict';

	var controller = null;
	var fontPickerIndex = 0;
	var customRuleIndex = Date.now();

	function normalizeSearch(value) {
		return String(value || '').toLocaleLowerCase().replace(/\s+/g, ' ').trim();
	}

	function visibleFontOptions(wrapper) {
		return Array.prototype.filter.call(wrapper.querySelectorAll('.sefm-font-option'), function (option) {
			return !option.hidden;
		});
	}

	function filterFontOptions(wrapper, query) {
		var needle = normalizeSearch(query);
		var hasMatch = false;

		wrapper.querySelectorAll('.sefm-font-group').forEach(function (group) {
			var groupMatch = false;
			group.querySelectorAll('.sefm-font-option').forEach(function (option) {
				var matches = !needle || normalizeSearch(option.getAttribute('data-search')).indexOf(needle) !== -1;
				option.hidden = !matches;
				if (matches) {
					groupMatch = true;
					hasMatch = true;
				}
			});
			group.hidden = !groupMatch;
		});

		var empty = wrapper.querySelector('.sefm-font-empty');
		if (empty) {
			empty.hidden = hasMatch;
		}
	}

	function closeFontPicker(wrapper, returnFocus) {
		if (!wrapper) {
			return;
		}

		var panel = wrapper.querySelector('.sefm-font-picker-panel');
		var button = wrapper.querySelector('.sefm-font-picker-button');
		var search = wrapper.querySelector('.sefm-font-search');
		if (!panel || !button) {
			return;
		}

		panel.hidden = true;
		wrapper.classList.remove('is-open');
		button.setAttribute('aria-expanded', 'false');
		if (search) {
			search.value = '';
			filterFontOptions(wrapper, '');
		}
		if (returnFocus) {
			button.focus();
		}
	}

	function closeOtherFontPickers(except) {
		document.querySelectorAll('.sefm-font-picker.is-open').forEach(function (wrapper) {
			if (wrapper !== except) {
				closeFontPicker(wrapper, false);
			}
		});
	}

	function openFontPicker(wrapper) {
		var panel = wrapper.querySelector('.sefm-font-picker-panel');
		var button = wrapper.querySelector('.sefm-font-picker-button');
		var search = wrapper.querySelector('.sefm-font-search');
		if (!panel || !button || !search) {
			return;
		}

		closeOtherFontPickers(wrapper);
		populateFontOptions(wrapper);
		panel.hidden = false;
		wrapper.classList.add('is-open');
		button.setAttribute('aria-expanded', 'true');
		filterFontOptions(wrapper, '');
		search.focus();
		search.select();

		var selected = wrapper.querySelector('.sefm-font-option[aria-selected="true"]');
		if (selected && typeof selected.scrollIntoView === 'function') {
			selected.scrollIntoView({block: 'nearest'});
		}
	}

	function chooseFontOption(wrapper, option) {
		var select = wrapper.querySelector('select.sefm-font-select');
		var label = wrapper.querySelector('.sefm-font-picker-value');
		if (!select || !option) {
			return;
		}

		select.value = option.getAttribute('data-value');
		if (label) {
			label.textContent = option.textContent;
		}
		var pickerButton = wrapper.querySelector('.sefm-font-picker-button');
		if (pickerButton) {
			var prefix = pickerButton.getAttribute('data-label-prefix') || '';
			pickerButton.setAttribute('aria-label', prefix ? prefix + ': ' + option.textContent : option.textContent);
		}
		wrapper.querySelectorAll('.sefm-font-option').forEach(function (item) {
			item.setAttribute('aria-selected', item === option ? 'true' : 'false');
		});
		select.dispatchEvent(new Event('change', {bubbles: true}));
		closeFontPicker(wrapper, true);
	}

	function appendFontOption(list, nativeOption, groupLabel) {
		var option = document.createElement('button');
		option.type = 'button';
		option.className = 'sefm-font-option';
		option.setAttribute('role', 'option');
		option.setAttribute('data-value', nativeOption.value);
		option.setAttribute('data-search', nativeOption.textContent + ' ' + (groupLabel || ''));
		option.setAttribute('aria-selected', nativeOption.selected ? 'true' : 'false');
		option.textContent = nativeOption.textContent;
		if (nativeOption.disabled) {
			option.disabled = true;
		}
		list.appendChild(option);
	}

	function populateFontOptions(wrapper) {
		var list = wrapper.querySelector('.sefm-font-options');
		var select = wrapper.querySelector('select.sefm-font-select');
		if (!list || !select || list.getAttribute('data-sefm-built') === '1') {
			return;
		}

		Array.prototype.forEach.call(select.children, function (child) {
			var group = document.createElement('div');
			group.className = 'sefm-font-group';

			if (child.tagName === 'OPTGROUP') {
				var heading = document.createElement('div');
				heading.className = 'sefm-font-group-label';
				heading.textContent = child.label;
				group.appendChild(heading);
				Array.prototype.forEach.call(child.children, function (nativeOption) {
					appendFontOption(group, nativeOption, child.label);
				});
			} else if (child.tagName === 'OPTION') {
				appendFontOption(group, child, '');
			}

			if (group.querySelector('.sefm-font-option')) {
				list.appendChild(group);
			}
		});
		list.setAttribute('data-sefm-built', '1');
	}

	function enhanceFontSelect(select) {
		if (!select || select.getAttribute('data-sefm-font-picker') === '1') {
			return;
		}
		select.setAttribute('data-sefm-font-picker', '1');

		var wrapper = document.createElement('div');
		wrapper.className = 'sefm-font-picker';
		var listId = 'sefm-font-list-' + (++fontPickerIndex);

		var currentOption = select.options[select.selectedIndex];
		var currentLabel = currentOption ? currentOption.textContent : '';
		var field = select.closest('.sefm-font-field');
		var fieldCaption = field ? field.querySelector('span') : null;
		var fieldLabel = fieldCaption ? fieldCaption.textContent : '';

		var button = document.createElement('button');
		button.type = 'button';
		button.className = 'sefm-font-picker-button';
		button.setAttribute('role', 'combobox');
		button.setAttribute('aria-haspopup', 'listbox');
		button.setAttribute('aria-expanded', 'false');
		button.setAttribute('aria-controls', listId);
		button.setAttribute('data-label-prefix', fieldLabel);
		button.setAttribute('aria-label', fieldLabel ? fieldLabel + ': ' + currentLabel : currentLabel);

		var value = document.createElement('span');
		value.className = 'sefm-font-picker-value';
		value.textContent = currentLabel;
		button.appendChild(value);


		var panel = document.createElement('div');
		panel.className = 'sefm-font-picker-panel';
		panel.hidden = true;

		var search = document.createElement('input');
		search.type = 'search';
		search.className = 'sefm-font-search';
		search.autocomplete = 'off';
		search.spellcheck = false;
		search.placeholder = typeof window.sbtyAdmin !== 'undefined' ? window.sbtyAdmin.fontSearch : 'Search fonts…';
		search.setAttribute('aria-label', search.placeholder);
		search.setAttribute('aria-controls', listId);
		panel.appendChild(search);

		var list = document.createElement('div');
		list.className = 'sefm-font-options';
		list.id = listId;
		list.setAttribute('role', 'listbox');

		panel.appendChild(list);

		var empty = document.createElement('p');
		empty.className = 'sefm-font-empty';
		empty.hidden = true;
		empty.textContent = typeof window.sbtyAdmin !== 'undefined' ? window.sbtyAdmin.noFontsFound : 'No matching fonts found.';
		panel.appendChild(empty);

		select.parentNode.insertBefore(wrapper, select);
		wrapper.appendChild(button);
		wrapper.appendChild(panel);
		wrapper.appendChild(select);
		select.classList.add('sefm-font-select-native');
		select.tabIndex = -1;
		select.setAttribute('aria-hidden', 'true');

		button.addEventListener('click', function () {
			if (wrapper.classList.contains('is-open')) {
				closeFontPicker(wrapper, false);
			} else {
				openFontPicker(wrapper);
			}
		});

		button.addEventListener('keydown', function (event) {
			if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
				event.preventDefault();
				openFontPicker(wrapper);
			}
		});

		search.addEventListener('input', function () {
			filterFontOptions(wrapper, search.value);
		});

		search.addEventListener('keydown', function (event) {
			var options = visibleFontOptions(wrapper);
			var selectedOption = wrapper.querySelector('.sefm-font-option[aria-selected="true"]');
			var preferredOption = !normalizeSearch(search.value) && selectedOption && !selectedOption.hidden ? selectedOption : options[0];
			if (event.key === 'Escape') {
				event.preventDefault();
				closeFontPicker(wrapper, true);
			} else if (event.key === 'ArrowDown' && preferredOption) {
				event.preventDefault();
				preferredOption.focus();
			} else if (event.key === 'Enter' && preferredOption) {
				event.preventDefault();
				chooseFontOption(wrapper, preferredOption);
			}
		});

		list.addEventListener('click', function (event) {
			var option = event.target.closest('.sefm-font-option');
			if (option && !option.disabled) {
				chooseFontOption(wrapper, option);
			}
		});

		list.addEventListener('keydown', function (event) {
			var option = event.target.closest('.sefm-font-option');
			if (!option) {
				return;
			}

			var options = visibleFontOptions(wrapper);
			var index = options.indexOf(option);
			if (event.key === 'Escape') {
				event.preventDefault();
				closeFontPicker(wrapper, true);
			} else if (event.key === 'ArrowDown' && index < options.length - 1) {
				event.preventDefault();
				options[index + 1].focus();
			} else if (event.key === 'ArrowUp') {
				event.preventDefault();
				if (index > 0) {
					options[index - 1].focus();
				} else {
					search.focus();
				}
			} else if (event.key === 'Home' && options.length) {
				event.preventDefault();
				options[0].focus();
			} else if (event.key === 'End' && options.length) {
				event.preventDefault();
				options[options.length - 1].focus();
			}
		});
	}

	function initFontPickers(root) {
		(root || document).querySelectorAll('select.sefm-font-select').forEach(enhanceFontSelect);
	}

	function syncFontPickerFromSelect(select) {
		if (!select) {
			return;
		}
		var wrapper = select.closest('.sefm-font-picker');
		if (!wrapper) {
			return;
		}
		var currentOption = select.options[select.selectedIndex];
		var currentLabel = currentOption ? currentOption.textContent : '';
		var label = wrapper.querySelector('.sefm-font-picker-value');
		if (label) {
			label.textContent = currentLabel;
		}
		var button = wrapper.querySelector('.sefm-font-picker-button');
		if (button) {
			var prefix = button.getAttribute('data-label-prefix') || '';
			button.setAttribute('aria-label', prefix ? prefix + ': ' + currentLabel : currentLabel);
		}
		wrapper.querySelectorAll('.sefm-font-option').forEach(function (option) {
			option.setAttribute('aria-selected', option.getAttribute('data-value') === select.value ? 'true' : 'false');
		});
	}

	function assignmentLayerActiveCount(panel) {
		if (!panel) {
			return 0;
		}
		var count = 0;
		panel.querySelectorAll('select.sefm-font-select').forEach(function (select) {
			if (select.value !== 'inherit') {
				count++;
			}
		});
		panel.querySelectorAll('select').forEach(function (select) {
			if (select.classList.contains('sefm-font-select')) {
				return;
			}
			if (/\[(?:weight|style|transform|direction)(?:_(?:desktop|mobile|tablet))?\]$/.test(select.name || '') && select.value !== 'inherit') {
				count++;
			}
		});
		panel.querySelectorAll('input[type="number"]').forEach(function (input) {
			if (/\[(?:font_size|line_height|letter_spacing)(?:_(?:desktop|mobile|tablet))?_value\]$/.test(input.name || '') && String(input.value || '').trim() !== '') {
				count++;
			}
		});
		panel.querySelectorAll('input[type="checkbox"]').forEach(function (checkbox) {
			if (/\[color(?:_(?:desktop|mobile|tablet))?_enabled\]$/.test(checkbox.name || '') && checkbox.checked) {
				count++;
			}
		});
		return count;
	}

	function assignmentActiveCount(fields) {
		if (!fields) {
			return 0;
		}
		var count = 0;
		fields.querySelectorAll('.sefm-layer-panel').forEach(function (panel) {
			count += assignmentLayerActiveCount(panel);
		});
		return count;
	}

	function activeLabel(count) {
		var config = window.sbtyAdmin || {};
		var format = count === 1 ? config.activeOne : config.activeMany;
		format = format || '%d active';
		return String(format).replace('%d', String(count));
	}

	function updateAssignmentState(fields) {
		if (!fields) {
			return;
		}
		var count = assignmentActiveCount(fields);
		var indicator = fields.querySelector('.sefm-active-indicator');
		var reset = fields.querySelector('.sefm-reset-assignment');
		if (indicator) {
			indicator.textContent = activeLabel(count);
			indicator.hidden = count === 0;
		}
		if (reset) {
			reset.disabled = count === 0;
		}
		fields.querySelectorAll('.sefm-layer-tab').forEach(function (button) {
			var layer = button.getAttribute('data-sefm-layer');
			var panel = fields.querySelector('.sefm-layer-panel[data-sefm-layer-panel="' + layer + '"]');
			var layerCount = assignmentLayerActiveCount(panel);
			var badge = button.querySelector('.sefm-layer-count');
			if (badge) {
				badge.textContent = String(layerCount);
				badge.hidden = layerCount === 0;
			}
		});
	}

	function initAssignmentStates(root) {
		(root || document).querySelectorAll('.sefm-assignment-fields').forEach(updateAssignmentState);
	}

	function switchAssignmentLayer(fields, layer) {
		if (!fields || !/^(general|desktop|mobile|tablet)$/.test(layer || '')) {
			return;
		}
		fields.setAttribute('data-sefm-active-layer', layer);
		fields.querySelectorAll('.sefm-layer-tab').forEach(function (button) {
			var active = button.getAttribute('data-sefm-layer') === layer;
			button.classList.toggle('is-active', active);
			button.setAttribute('aria-selected', active ? 'true' : 'false');
			button.setAttribute('tabindex', active ? '0' : '-1');
		});
		fields.querySelectorAll('.sefm-layer-panel').forEach(function (panel) {
			panel.hidden = panel.getAttribute('data-sefm-layer-panel') !== layer;
		});
	}

	function initLayerSwitchers(root) {
		(root || document).querySelectorAll('.sefm-assignment-fields').forEach(function (fields) {
			var layer = fields.getAttribute('data-sefm-active-layer') || 'general';
			switchAssignmentLayer(fields, layer);
		});
	}

	function resetAssignment(fields) {
		if (!fields) {
			return;
		}
		fields.querySelectorAll('select').forEach(function (select) {
			if (select.classList.contains('sefm-font-select') || /\[(?:weight|style|transform|direction)(?:_(?:desktop|mobile|tablet))?\]$/.test(select.name || '')) {
				select.value = 'inherit';
				if (select.classList.contains('sefm-font-select')) {
					syncFontPickerFromSelect(select);
				}
			}
		});
		fields.querySelectorAll('input[type="number"]').forEach(function (input) {
			if (/\[(?:font_size|line_height|letter_spacing)(?:_(?:desktop|mobile|tablet))?_value\]$/.test(input.name || '')) {
				input.value = '';
			}
		});
		fields.querySelectorAll('input[type="checkbox"]').forEach(function (checkbox) {
			if (/\[color(?:_(?:desktop|mobile|tablet))?_enabled\]$/.test(checkbox.name || '')) {
				checkbox.checked = false;
			}
		});
		fields.querySelectorAll('.sefm-advanced').forEach(function (advanced) {
			advanced.open = false;
		});
		fields.querySelectorAll('input, select').forEach(function (control) {
			control.dispatchEvent(new Event('change', {bubbles: true}));
		});
		switchAssignmentLayer(fields, 'general');
		updateAssignmentState(fields);
	}

	function elementSearchItems() {
		var config = window.sbtyAdmin || {};
		return Array.isArray(config.elementSearchIndex) ? config.elementSearchIndex : [];
	}

	function initAvailableFontList(root) {
		var scope = root || document;
		var list = scope.querySelector('[data-sefm-available-font-list]');
		if (!list || list.getAttribute('data-sefm-search-init') === '1') {
			return;
		}

		var panel = list.closest('.sefm-panel');
		var input = panel ? panel.querySelector('#sefm-available-font-search-input') : null;
		if (!input) {
			return;
		}

		var rows = Array.prototype.slice.call(list.querySelectorAll('[data-sefm-font-row]'));
		var empty = list.querySelector('[data-sefm-font-list-empty]');
		var clearButton = panel.querySelector('[data-sefm-font-search-clear]');
		if (clearButton) {
			clearButton.parentNode.classList.add('has-clear');
		}
		list.setAttribute('data-sefm-search-init', '1');

		function filterAvailableFonts() {
			var needle = normalizeSearch(input.value);
			var matches = 0;
			rows.forEach(function (row) {
				var haystack = normalizeSearch(row.getAttribute('data-search'));
				var visible = !needle || haystack.indexOf(needle) !== -1;
				row.hidden = !visible;
				row.setAttribute('aria-hidden', visible ? 'false' : 'true');
				if (visible) {
					matches += 1;
				}
			});
			if (empty) {
				empty.hidden = matches !== 0;
			}
			if (clearButton) {
				clearButton.hidden = input.value.length === 0;
			}
			list.scrollTop = 0;
		}

		function clearAvailableFontSearch() {
			input.value = '';
			input.focus();
			input.dispatchEvent(new Event('input', {bubbles: true}));
		}

		/* Filter on every keystroke; this also covers pasted text and mobile input. */
		input.addEventListener('input', filterAvailableFonts);
		input.addEventListener('search', filterAvailableFonts);
		input.addEventListener('change', filterAvailableFonts);
		if (clearButton) {
			clearButton.addEventListener('click', clearAvailableFontSearch);
		}
		input.addEventListener('keydown', function (event) {
			if (event.key === 'Escape' && input.value) {
				event.preventDefault();
				clearAvailableFontSearch();
			}
		});
		filterAvailableFonts();
	}

	function closeElementSearch(wrapper) {
		if (!wrapper) {
			return;
		}
		var input = wrapper.querySelector('#sefm-element-search-input');
		var results = wrapper.querySelector('.sefm-element-search-results');
		if (results) {
			results.hidden = true;
			results.innerHTML = '';
		}
		if (input) {
			input.setAttribute('aria-expanded', 'false');
			input.removeAttribute('aria-activedescendant');
		}
	}

	function renderElementSearch(wrapper, query) {
		if (!wrapper) {
			return;
		}
		var input = wrapper.querySelector('#sefm-element-search-input');
		var results = wrapper.querySelector('.sefm-element-search-results');
		var needle = normalizeSearch(query);
		if (!input || !results || !needle) {
			closeElementSearch(wrapper);
			return;
		}

		var matches = elementSearchItems().filter(function (item) {
			return normalizeSearch([item.label, item.group, item.description, item.selector].join(' ')).indexOf(needle) !== -1;
		}).slice(0, 12);
		results.innerHTML = '';

		if (!matches.length) {
			var empty = document.createElement('p');
			empty.className = 'sefm-element-search-empty';
			empty.textContent = (window.sbtyAdmin && window.sbtyAdmin.noElementsFound) || 'No matching elements found.';
			results.appendChild(empty);
		} else {
			matches.forEach(function (item, index) {
				var link = document.createElement('a');
				link.href = item.url;
				link.className = 'sefm-element-search-result';
				link.id = 'sefm-element-search-option-' + index;
				link.setAttribute('role', 'option');
				link.setAttribute('data-sefm-search-result', '1');
				var title = document.createElement('strong');
				title.textContent = item.label;
				var meta = document.createElement('span');
				meta.className = 'sefm-element-search-meta';
				var group = document.createElement('b');
				group.textContent = item.group;
				var selector = document.createElement('code');
				selector.textContent = item.selector;
				selector.title = item.selector;
				meta.appendChild(group);
				meta.appendChild(selector);
				link.appendChild(title);
				link.appendChild(meta);
				results.appendChild(link);
			});
		}
		results.hidden = false;
		input.setAttribute('aria-expanded', 'true');
	}

	function initElementSearch(root) {
		var wrapper = (root || document).querySelector('.sefm-element-search');
		if (!wrapper || wrapper.getAttribute('data-sefm-search-init') === '1') {
			return;
		}
		wrapper.setAttribute('data-sefm-search-init', '1');
		var input = wrapper.querySelector('#sefm-element-search-input');
		var results = wrapper.querySelector('.sefm-element-search-results');
		if (!input || !results) {
			return;
		}
		input.addEventListener('input', function () {
			renderElementSearch(wrapper, input.value);
		});
		input.addEventListener('keydown', function (event) {
			var options = Array.prototype.slice.call(results.querySelectorAll('.sefm-element-search-result'));
			if (event.key === 'Escape') {
				closeElementSearch(wrapper);
			} else if ((event.key === 'ArrowDown' || event.key === 'ArrowUp') && options.length) {
				event.preventDefault();
				var target = event.key === 'ArrowDown' ? options[0] : options[options.length - 1];
				target.focus();
				input.setAttribute('aria-activedescendant', target.id);
			} else if (event.key === 'Enter' && options.length) {
				event.preventDefault();
				options[0].click();
			}
		});
		results.addEventListener('keydown', function (event) {
			var option = event.target.closest('.sefm-element-search-result');
			if (!option) {
				return;
			}
			var options = Array.prototype.slice.call(results.querySelectorAll('.sefm-element-search-result'));
			var index = options.indexOf(option);
			if (event.key === 'ArrowDown' && index < options.length - 1) {
				event.preventDefault();
				options[index + 1].focus();
			} else if (event.key === 'ArrowUp') {
				event.preventDefault();
				if (index > 0) {
					options[index - 1].focus();
				} else {
					input.focus();
				}
			} else if (event.key === 'Escape') {
				event.preventDefault();
				closeElementSearch(wrapper);
				input.focus();
			}
		});
	}

	function focusHashTarget(url, smooth) {
		var parsed;
		try {
			parsed = new URL(url || window.location.href, window.location.href);
		} catch (error) {
			return false;
		}
		if (!parsed.hash) {
			return false;
		}
		var targetId;
		try {
			targetId = decodeURIComponent(parsed.hash.slice(1));
		} catch (error) {
			return false;
		}
		var target = document.getElementById(targetId);
		if (!target) {
			return false;
		}
		target.classList.add('sefm-search-target');
		window.setTimeout(function () { target.classList.remove('sefm-search-target'); }, 1800);
		target.scrollIntoView({behavior: smooth ? 'smooth' : 'auto', block: 'center'});
		return true;
	}

	function isPluginLink(link) {
		if (!link || link.target || link.hasAttribute('download')) {
			return false;
		}

		var url;
		try {
			url = new URL(link.href, window.location.href);
		} catch (error) {
			return false;
		}

		return url.origin === window.location.origin &&
			url.searchParams.get('page') === 'saeidbakhsh-typography-manager' &&
			(link.closest('.sefm-tabs') || link.closest('.sefm-subtabs') || link.closest('.sefm-element-search-results'));
	}

	var activeUrl = window.location.href;

	function showPage(main, url, pushState) {
		closeOtherFontPickers(null);
		var current = document.querySelector('.sefm-main');
		if (current !== main) { current.replaceWith(main); }
		window.sbtyWorkspace.remember(url, main);
		var tab = new URL(url, window.location.href).searchParams.get('tab') || 'typography';
		document.querySelectorAll('.sefm-tabs a').forEach(function (link) {
			var active = (new URL(link.href).searchParams.get('tab') || 'typography') === tab;
			link.classList.toggle('is-active', active);
			if (active) { link.setAttribute('aria-current', 'page'); }
			else { link.removeAttribute('aria-current'); }
		});
		if (pushState && url !== window.location.href) {
			window.history.pushState({sefm: true}, '', url);
		}
		activeUrl = url;
		initFontPickers(main);
		initLayerSwitchers(main);
		initAssignmentStates(main);
		initAvailableFontList(main);
		initElementSearch(document);
		main.setAttribute('tabindex', '-1');
		main.focus({preventScroll: true});
		if (!focusHashTarget(url, false)) {
			window.scrollTo({top: Math.max(0, document.querySelector('.sefm-tabs').offsetTop - 40), behavior: 'auto'});
		}
	}

	function navigate(url, pushState) {
		if (!window.sbtyWorkspace) {
			window.location.assign(url);
			return;
		}
		if (controller) { controller.abort(); }
		controller = null;
		var wrap = document.querySelector('.sefm-wrap');
		wrap.classList.remove('is-loading');
		var cached = window.sbtyWorkspace.get(url);
		if (cached) {
			showPage(cached.main, url, pushState);
			return;
		}

		controller = new AbortController();
		var requestController = controller;
		wrap.classList.add('is-loading');
		fetch(url, {
			credentials: 'same-origin',
			cache: 'no-store',
			headers: {'X-Requested-With': 'XMLHttpRequest'},
			signal: requestController.signal
		}).then(function (response) {
			if (!response.ok) { throw new Error('HTTP ' + response.status); }
			return response.text();
		}).then(function (html) {
			if (controller !== requestController) { return; }
			var incoming = new DOMParser().parseFromString(html, 'text/html');
			var main = incoming.querySelector('.sefm-wrap .sefm-main');
			if (!main || !incoming.querySelector('.sefm-tabs')) { throw new Error('Missing settings region'); }
			showPage(main, url, pushState);
		}).catch(function (error) {
			if (controller === requestController && error.name !== 'AbortError') {
				window.sbtyWorkspace.notice(window.sbtyAdmin.navError, true);
				// Keep the draft and visible page instead of navigating away on error.
				if (!pushState) { window.history.replaceState({sefm: true}, '', activeUrl); }
			}
		}).finally(function () {
			if (controller === requestController) {
				wrap.classList.remove('is-loading');
				controller = null;
			}
		});
	}

	window.addEventListener('sbty-workspace-navigate', function (event) {
		navigate(event.detail, true);
	});

	document.addEventListener('click', function (event) {
		var openPicker = event.target.closest('.sefm-font-picker');
		if (!openPicker) {
			closeOtherFontPickers(null);
		}
		var elementSearch = event.target.closest('.sefm-element-search');
		if (!elementSearch) {
			closeElementSearch(document.querySelector('.sefm-element-search'));
		}

		var layerButton = event.target.closest('.sefm-layer-tab');
		if (layerButton) {
			event.preventDefault();
			switchAssignmentLayer(layerButton.closest('.sefm-assignment-fields'), layerButton.getAttribute('data-sefm-layer'));
			return;
		}

		var link = event.target.closest('a');
		if (event.button === 0 && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey && isPluginLink(link)) {
			event.preventDefault();
			var destination = new URL(link.href, window.location.href);
			var current = new URL(window.location.href);
			if (link.closest('.sefm-element-search-results') && destination.pathname === current.pathname && destination.search === current.search && destination.hash) {
				window.history.pushState({sefm: true}, '', destination.href);
				closeElementSearch(document.querySelector('.sefm-element-search'));
				focusHashTarget(destination.href, true);
				return;
			}
			navigate(link.href, true);
			return;
		}

		var confirmTarget = event.target.closest('[data-sefm-confirm]');
		if (confirmTarget && typeof window.sbtyAdmin !== 'undefined') {
			var type = confirmTarget.getAttribute('data-sefm-confirm');
			var message = type === 'reset' ? window.sbtyAdmin.resetConfirm : window.sbtyAdmin.deleteConfirm;
			if (!window.confirm(message)) {
				event.preventDefault();
			}
		}

		var addButton = event.target.closest('#sefm-add-rule');
		if (addButton) {
			var rules = document.getElementById('sefm-custom-rules');
			var template = document.getElementById('sefm-custom-template');
			if (rules && template && rules.querySelectorAll('.sefm-custom-rule').length < 20) {
				rules.insertAdjacentHTML('beforeend', template.innerHTML.replace(/__INDEX__/g, String(++customRuleIndex)));
				initFontPickers(rules);
				initLayerSwitchers(rules);
				initAssignmentStates(rules);
				if (window.sbtyWorkspace) { window.sbtyWorkspace.changed(rules); }
			}
		}

		var removeButton = event.target.closest('.sefm-remove-rule');
		if (removeButton) {
			var row = removeButton.closest('.sefm-custom-rule');
			if (row) {
				if (window.sbtyWorkspace) { window.sbtyWorkspace.changed(row); }
				row.remove();
			}
		}

		var resetAssignmentButton = event.target.closest('.sefm-reset-assignment');
		if (resetAssignmentButton) {
			event.preventDefault();
			resetAssignment(resetAssignmentButton.closest('.sefm-assignment-fields'));
		}

		var copyButton = event.target.closest('#sefm-copy-css');
		if (copyButton) {
			var preview = document.getElementById('sefm-css-preview');
			var css = preview ? preview.textContent : '';
			if (navigator.clipboard && css) {
				navigator.clipboard.writeText(css).then(function () {
					var original = copyButton.textContent;
					copyButton.textContent = window.sbtyAdmin.copied;
					window.setTimeout(function () { copyButton.textContent = original; }, 1600);
				});
			}
		}
	});

	document.addEventListener('keydown', function (event) {
		var layerButton = event.target.closest('.sefm-layer-tab');
		if (!layerButton || ['ArrowLeft', 'ArrowRight', 'Home', 'End'].indexOf(event.key) === -1) {
			return;
		}
		var fields = layerButton.closest('.sefm-assignment-fields');
		var tabs = Array.prototype.slice.call(fields.querySelectorAll('.sefm-layer-tab'));
		var index = tabs.indexOf(layerButton);
		var next = index;
		if (event.key === 'Home') {
			next = 0;
		} else if (event.key === 'End') {
			next = tabs.length - 1;
		} else if (event.key === 'ArrowRight') {
			next = (index + 1) % tabs.length;
		} else if (event.key === 'ArrowLeft') {
			next = (index - 1 + tabs.length) % tabs.length;
		}
		event.preventDefault();
		switchAssignmentLayer(fields, tabs[next].getAttribute('data-sefm-layer'));
		tabs[next].focus();
	});

	document.addEventListener('input', function (event) {
		var fields = event.target.closest('.sefm-assignment-fields');
		if (fields) {
			updateAssignmentState(fields);
		}
	});

	document.addEventListener('change', function (event) {
		var fields = event.target.closest('.sefm-assignment-fields');
		if (fields) {
			updateAssignmentState(fields);
		}
	});

	document.addEventListener('submit', function (event) {
		var form = event.target.closest('[data-sefm-confirm-form]');
		if (!form || typeof window.sbtyAdmin === 'undefined') {
			return;
		}
		var type = form.getAttribute('data-sefm-confirm-form');
		var message = type === 'reset' ? window.sbtyAdmin.resetConfirm : window.sbtyAdmin.deleteConfirm;
		if (!window.confirm(message)) {
			event.preventDefault();
		}
	});

	window.addEventListener('popstate', function () {
		if (new URL(window.location.href).searchParams.get('page') === 'saeidbakhsh-typography-manager') {
			navigate(window.location.href, false);
		}
	});

	initFontPickers(document);
	initLayerSwitchers(document);
	initAssignmentStates(document);
	initAvailableFontList(document);
	initElementSearch(document);
	focusHashTarget(window.location.href, false);
}());
