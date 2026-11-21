/**
 * Shared registration helper for per-element Alpine engines.
 *
 * Each public-form "engine" (for example the calculation reactivity or the
 * person name display format reactivity that the fieldtype families add) follows
 * the same lifecycle:
 *
 *   1. Wait for Alpine to finish booting (so the form's `x-data` proxy is
 *      reachable via `Alpine.$data(form)`).
 *   2. Scan the page for elements matching a selector.
 *   3. Skip elements that have already been wired up (idempotent re-runs).
 *   4. Mark each wired element with a flag attribute and hand it to a
 *      per-engine `init(element)` callback.
 *
 * Centralizing the lifecycle here means a new engine is roughly fifteen
 * lines: an `initElement` function plus one `registerAlpineEngine(...)`
 * call. Bug fixes (e.g., a new Alpine bootstrap event in a future
 * Statamic / Alpine release) land in one place.
 */

/**
 * @typedef {Object} RegisterAlpineEngineOptions
 * @property {string} selector
 *   CSS selector matching elements this engine should bind to.
 * @property {string} boundAttr
 *   Camel-cased `dataset` key used as the idempotency flag. Becomes the
 *   `data-<kebab>` HTML attribute. E.g., `"calculationBound"` →
 *   `data-calculation-bound="1"` once the element is wired.
 * @property {(el: Element) => void} init
 *   Engine-specific binding logic. Called once per matched element after
 *   Alpine has initialized.
 */

/**
 * Whether Alpine has begun initializing the page's tree.
 *
 * `Alpine.version` is set when the module is imported, so it proves nothing about
 * start order. Alpine marks each `x-data` element with `_x_dataStack` while it
 * initializes the tree, so the first such element carrying it is the signal that
 * `Alpine.start()` has run. That is "begun", not "finished": the mark is set
 * before `alpine:initialized` fires. While the page is still parsing the answer is
 * always no, because the document may not hold the `x-data` element yet. A complete
 * page with no `x-data` element has nothing to wait for, so an early bind is
 * harmless there. Only the first `x-data` element is consulted; registering in the
 * gap between a host inserting a new first `x-data` element and Alpine reaching it
 * is not covered.
 *
 * @returns {boolean}
 */
function alpineHasStarted() {
	if (document.readyState === 'loading' || !window.Alpine) return false;

	const root = document.querySelector('[x-data]');

	return root === null || root._x_dataStack !== undefined;
}

/**
 * Register a per-element Alpine engine. Safe to call from a module's top
 * level, while the page parses and before or after `Alpine.start()`; binding
 * waits until Alpine has begun initializing the tree.
 *
 * One element whose `init` throws does not stop the others: the error is logged
 * with the selector, the element is left unbound so a later `bind()` retries it,
 * and the scan continues. Because a retry runs `init` again, `init` should be
 * idempotent or throw before it attaches listeners or watchers. Elements inserted after Alpine started are not bound
 * on their own; call the returned `bind()` after inserting them.
 *
 * @param {RegisterAlpineEngineOptions} options
 * @returns {() => void}
 *   Scans for unbound matching elements and binds them. Idempotent.
 */
export function registerAlpineEngine({ selector, boundAttr, init }) {
	const bindAll = () => {
		document.querySelectorAll(selector).forEach((el) => {
			if (el.dataset[boundAttr] === '1') return;
			el.dataset[boundAttr] = '1';

			try {
				init(el);
			} catch (error) {
				// Clear the flag so a later bind() retries this element.
				delete el.dataset[boundAttr];
				console.error(`registerAlpineEngine: init failed for selector "${selector}"`, error);
			}
		});
	};

	// Alpine fires `alpine:initialized` once, after it has initialized the tree.
	// Listen for it whenever Alpine has not provably started, which covers a
	// registration made while the page parses and one made after the module
	// loaded but before a deferred `Alpine.start()`.
	if (alpineHasStarted()) {
		queueMicrotask(bindAll);
	} else {
		document.addEventListener('alpine:initialized', bindAll, { once: true });
	}

	return bindAll;
}
