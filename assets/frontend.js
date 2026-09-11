(function () {
	'use strict';

	var config = window.sbtyFontRuntime;
	if (!config || !Array.isArray(config.rules) || !config.rules.length) {
		return;
	}

	var rules = config.rules;
	rules.forEach(function (rule, index) { rule._sbtyOwner = index + 1; });
	var queuedRoots = new Set();
	var originalStyles = new WeakMap();
	var frame = 0;
	var resizeFrame = 0;

	function rememberOriginal(element, property) {
		var state = originalStyles.get(element);
		if (!state) {
			state = {};
			originalStyles.set(element, state);
		}
		if (!Object.prototype.hasOwnProperty.call(state, property)) {
			state[property] = {
				value: element.style.getPropertyValue(property),
				priority: element.style.getPropertyPriority(property)
			};
		}
	}

	function enforceElement(element, declarations, owner) {
		Object.keys(declarations).forEach(function (property) {
			var value = String(declarations[property]);
			rememberOriginal(element, property);
			var state = originalStyles.get(element);
			state[property].owner = owner;
			if (element.style.getPropertyValue(property) !== value || element.style.getPropertyPriority(property) !== 'important') {
				element.style.setProperty(property, value, 'important');
			}
		});
	}

	function restoreElement(element, declarations, owner) {
		var state = originalStyles.get(element);
		if (!state) {
			return;
		}

		Object.keys(declarations).forEach(function (property) {
			if (!Object.prototype.hasOwnProperty.call(state, property) || state[property].owner !== owner) {
				return;
			}
			var original = state[property];
			if (original.value) {
				element.style.setProperty(property, original.value, original.priority || '');
			} else {
				element.style.removeProperty(property);
			}
			delete state[property];
		});
	}

	function ruleMatchesMedia(rule) {
		if (!rule.media) {
			return true;
		}
		if (!window.matchMedia) {
			return false;
		}
		try {
			return window.matchMedia(rule.media).matches;
		} catch (error) {
			return false;
		}
	}

	function visitRuleElements(root, rule, callback, toolbar) {
		if (!root || (root.nodeType !== 1 && root.nodeType !== 9)) {
			return;
		}
		try {
			if (root.nodeType === 1 && (!toolbar || !toolbar.contains(root)) && root.matches(rule.selector)) {
				callback(root, rule.declarations, rule._sbtyOwner);
			}
			root.querySelectorAll(rule.selector).forEach(function (element) {
				if (!toolbar || !toolbar.contains(element)) {
					callback(element, rule.declarations, rule._sbtyOwner);
				}
			});
		} catch (error) {
			// A browser may not understand a modern custom selector. CSS remains active.
		}
	}

	function enforceRoot(root) {
		var toolbar = document.getElementById('wpadminbar');
		if (toolbar && toolbar.contains(root)) {
			return;
		}
		// Restore responsive declarations that are no longer active first. Active
		// rules are then applied in their saved cascade order, so resizing never
		// leaves a stale mobile/tablet inline !important value behind.
		rules.forEach(function (rule) {
			if (rule.media && !ruleMatchesMedia(rule)) {
				visitRuleElements(root, rule, restoreElement, toolbar);
			}
		});

		rules.forEach(function (rule) {
			if (ruleMatchesMedia(rule)) {
				visitRuleElements(root, rule, enforceElement, toolbar);
			}
		});
	}

	function flush() {
		frame = 0;
		queuedRoots.forEach(enforceRoot);
		queuedRoots.clear();
	}

	function queue(root) {
		if (!root) {
			return;
		}
		var toolbar = document.getElementById('wpadminbar');
		if (toolbar && toolbar.contains(root)) {
			return;
		}
		queuedRoots.add(root);
		if (!frame) {
			frame = window.requestAnimationFrame(flush);
		}
	}

	function queueViewportRefresh() {
		if (resizeFrame) {
			return;
		}
		resizeFrame = window.requestAnimationFrame(function () {
			resizeFrame = 0;
			queue(document);
		});
	}

	function start() {
		enforceRoot(document);
		window.addEventListener('resize', queueViewportRefresh, {passive: true});

		if (!window.MutationObserver) {
			return;
		}

		var observer = new MutationObserver(function (mutations) {
			mutations.forEach(function (mutation) {
				if (mutation.type === 'attributes') {
					queue(mutation.target);
					return;
				}

				mutation.addedNodes.forEach(function (node) {
					if (node.nodeType === 1) {
						queue(node);
					}
				});
			});
		});

		observer.observe(document.documentElement, {
			attributes: true,
			attributeFilter: ['class', 'style'],
			childList: true,
			subtree: true
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start, {once: true});
	} else {
		start();
	}
}());
