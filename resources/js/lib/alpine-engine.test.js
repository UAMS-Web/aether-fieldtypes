import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { registerAlpineEngine } from './alpine-engine.js';

/**
 * A minimal DOM: elements matching the selector, each with a dataset the helper flags, and an
 * optional `x-data` root. `started` mirrors what Alpine does while it initializes the tree:
 * it sets `_x_dataStack` on every `x-data` element.
 *
 * @param {string} readyState
 * @param {{ ids?: string[], root?: 'none' | 'unstarted' | 'started' }} [options]
 */
function fakeDocument(readyState, { ids = ['a'], root = 'none' } = {}) {
	const elements = ids.map((id) => ({ id, dataset: {} }));
	const listeners = {};
	const rootElement = root === 'none' ? null : root === 'started' ? { _x_dataStack: [] } : {};

	return {
		el: elements[0],
		elements,
		listeners,
		document: {
			readyState,
			querySelector: (selector) => (selector === '[x-data]' ? rootElement : null),
			querySelectorAll: () => elements,
			addEventListener: (name, handler) => {
				listeners[name] = handler;
			},
		},
	};
}

describe('registerAlpineEngine', () => {
	beforeEach(() => {
		vi.stubGlobal('queueMicrotask', (callback) => callback());
	});

	afterEach(() => {
		vi.unstubAllGlobals();
		vi.restoreAllMocks();
	});

	describe('start order', () => {
		it('waits for alpine:initialized while the page is still parsing', () => {
			const { el, listeners, document } = fakeDocument('loading');
			vi.stubGlobal('document', document);
			vi.stubGlobal('window', {});
			const init = vi.fn();

			registerAlpineEngine({ selector: '[data-x]', boundAttr: 'xBound', init });

			// Nothing is bound until Alpine signals it is ready
			expect(init).not.toHaveBeenCalled();
			listeners['alpine:initialized']();
			expect(init).toHaveBeenCalledWith(el);
			expect(el.dataset.xBound).toBe('1');
		});

		it('binds at once when registered after Alpine started', () => {
			const { el, document } = fakeDocument('complete', { root: 'started' });
			vi.stubGlobal('document', document);
			vi.stubGlobal('window', { Alpine: { version: '3.0.0' } });
			const init = vi.fn();

			registerAlpineEngine({ selector: '[data-x]', boundAttr: 'xBound', init });

			expect(init).toHaveBeenCalledWith(el);
		});

		it('waits for alpine:initialized while the page parses even when Alpine is imported', () => {
			// A classic script can run mid-parse with `window.Alpine` set and no `x-data` element parsed yet
			const { listeners, document } = fakeDocument('loading');
			vi.stubGlobal('document', document);
			vi.stubGlobal('window', { Alpine: { version: '3.0.0' } });
			const init = vi.fn();

			registerAlpineEngine({ selector: '[data-x]', boundAttr: 'xBound', init });

			expect(init).not.toHaveBeenCalled();
			expect(typeof listeners['alpine:initialized']).toBe('function');
		});

		it('waits for alpine:initialized when Alpine is imported but start() has not run', () => {
			// `Alpine.version` is set at import, so a host that registers an engine and calls
			// `Alpine.start()` in a later module sees it set while the tree is still uninitialized.
			const { el, listeners, document } = fakeDocument('interactive', { root: 'unstarted' });
			vi.stubGlobal('document', document);
			vi.stubGlobal('window', { Alpine: { version: '3.0.0' } });
			const init = vi.fn();

			registerAlpineEngine({ selector: '[data-x]', boundAttr: 'xBound', init });

			expect(init).not.toHaveBeenCalled();
			listeners['alpine:initialized']();
			expect(init).toHaveBeenCalledWith(el);
		});

		it('waits for alpine:initialized when the page is ready but Alpine is absent', () => {
			const { listeners, document } = fakeDocument('complete');
			vi.stubGlobal('document', document);
			vi.stubGlobal('window', {});
			const init = vi.fn();

			registerAlpineEngine({ selector: '[data-x]', boundAttr: 'xBound', init });

			expect(init).not.toHaveBeenCalled();
			expect(typeof listeners['alpine:initialized']).toBe('function');
		});

		it('binds at once on a page with no x-data element once Alpine is present', () => {
			const { el, document } = fakeDocument('complete');
			vi.stubGlobal('document', document);
			vi.stubGlobal('window', { Alpine: { version: '3.0.0' } });
			const init = vi.fn();

			registerAlpineEngine({ selector: '[data-x]', boundAttr: 'xBound', init });

			expect(init).toHaveBeenCalledWith(el);
		});
	});

	it('skips an element that is already bound', () => {
		const { el, document } = fakeDocument('complete', { root: 'started' });
		el.dataset.xBound = '1';
		vi.stubGlobal('document', document);
		vi.stubGlobal('window', { Alpine: { version: '3.0.0' } });
		const init = vi.fn();

		registerAlpineEngine({ selector: '[data-x]', boundAttr: 'xBound', init });

		expect(init).not.toHaveBeenCalled();
	});

	describe('a throwing init', () => {
		it('leaves the other elements bound and logs the selector', () => {
			const { elements, document } = fakeDocument('complete', { ids: ['a', 'b', 'c'], root: 'started' });
			vi.stubGlobal('document', document);
			vi.stubGlobal('window', { Alpine: { version: '3.0.0' } });
			const error = vi.spyOn(console, 'error').mockImplementation(() => {});
			const boom = new Error('boom');
			const init = vi.fn((el) => {
				if (el.id === 'a') throw boom;
			});

			registerAlpineEngine({ selector: '[data-x]', boundAttr: 'xBound', init });

			expect(init).toHaveBeenCalledTimes(3);
			expect(elements.map((el) => el.dataset.xBound)).toEqual([undefined, '1', '1']);
			expect(error).toHaveBeenCalledTimes(1);
			expect(error.mock.calls[0][0]).toContain('"[data-x]"');
			expect(error.mock.calls[0][1]).toBe(boom);
		});

		it('is retried by a later bind()', () => {
			const { elements, document } = fakeDocument('complete', { ids: ['a', 'b'], root: 'started' });
			vi.stubGlobal('document', document);
			vi.stubGlobal('window', { Alpine: { version: '3.0.0' } });
			vi.spyOn(console, 'error').mockImplementation(() => {});
			let failing = true;
			const init = vi.fn((el) => {
				if (el.id === 'a' && failing) throw new Error('boom');
			});

			const bind = registerAlpineEngine({ selector: '[data-x]', boundAttr: 'xBound', init });
			failing = false;
			bind();

			expect(elements.map((el) => el.dataset.xBound)).toEqual(['1', '1']);
			// b was bound on the first pass and is not initialized twice
			expect(init.mock.calls.filter(([el]) => el.id === 'b')).toHaveLength(1);
		});
	});

	describe('elements inserted after Alpine started', () => {
		it('are not bound on their own and are bound by bind()', () => {
			const { elements, document } = fakeDocument('complete', { ids: ['a'], root: 'started' });
			vi.stubGlobal('document', document);
			vi.stubGlobal('window', { Alpine: { version: '3.0.0' } });
			const init = vi.fn();

			const bind = registerAlpineEngine({ selector: '[data-x]', boundAttr: 'xBound', init });
			expect(init).toHaveBeenCalledTimes(1);

			// A later insert: nothing observes the DOM, so nothing happens until the host asks
			elements.push({ id: 'late', dataset: {} });
			expect(init).toHaveBeenCalledTimes(1);

			bind();
			expect(init).toHaveBeenCalledTimes(2);
			expect(init.mock.calls[1][0].id).toBe('late');
		});
	});
});
