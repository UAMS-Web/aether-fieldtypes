import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { registerAlpineEngine } from './alpine-engine.js';

/**
 * A minimal DOM: one element matching the selector, with a dataset the helper flags.
 */
function fakeDocument(readyState) {
	const el = { dataset: {} };
	const listeners = {};

	return {
		el,
		listeners,
		document: {
			readyState,
			querySelectorAll: () => [el],
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
	});

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

	it('binds at once when Alpine has already booted', () => {
		const { el, document } = fakeDocument('complete');
		vi.stubGlobal('document', document);
		vi.stubGlobal('window', { Alpine: { version: '3.0.0' } });
		const init = vi.fn();

		registerAlpineEngine({ selector: '[data-x]', boundAttr: 'xBound', init });

		expect(init).toHaveBeenCalledWith(el);
	});

	it('waits for alpine:initialized when the page is ready but Alpine has not booted', () => {
		const { listeners, document } = fakeDocument('complete');
		vi.stubGlobal('document', document);
		vi.stubGlobal('window', {});
		const init = vi.fn();

		registerAlpineEngine({ selector: '[data-x]', boundAttr: 'xBound', init });

		expect(init).not.toHaveBeenCalled();
		expect(typeof listeners['alpine:initialized']).toBe('function');
	});

	it('skips an element that is already bound', () => {
		const { el, document } = fakeDocument('complete');
		el.dataset.xBound = '1';
		vi.stubGlobal('document', document);
		vi.stubGlobal('window', { Alpine: { version: '3.0.0' } });
		const init = vi.fn();

		registerAlpineEngine({ selector: '[data-x]', boundAttr: 'xBound', init });

		expect(init).not.toHaveBeenCalled();
	});
});
