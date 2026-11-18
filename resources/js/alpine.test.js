import { afterEach, describe, expect, it, vi } from 'vitest';
import * as publicEntry from './alpine.js';
import { registerAlpineEngine } from './lib/alpine-engine.js';

describe('the public Alpine entry', () => {
	it('exposes registerAlpineEngine for a host to import before Alpine.start()', () => {
		// The entry re-exports the shared helper itself, not a copy
		expect(publicEntry.registerAlpineEngine).toBe(registerAlpineEngine);
	});

	it('exposes nothing else yet', () => {
		// Each fieldtype family adds its engine here as it moves in
		expect(Object.keys(publicEntry)).toEqual(['registerAlpineEngine']);
	});
});

/**
 * A test-only engine, registered the way a family's engine registers: through the public entry,
 * against the elements a form partial marks with `data-test-engine`.
 */
describe('a test-only engine registered through the public entry', () => {
	afterEach(() => {
		vi.unstubAllGlobals();
	});

	it('binds once to each element the form partial marks, after Alpine has initialized', () => {
		// Two elements the partial marks, one of them already bound by an earlier run
		const fresh = { dataset: {} };
		const bound = { dataset: { testEngineBound: '1' } };
		const listeners = {};
		vi.stubGlobal('window', {});
		vi.stubGlobal('document', {
			readyState: 'loading',
			querySelectorAll: (selector) => (selector === '[data-test-engine]' ? [fresh, bound] : []),
			addEventListener: (name, handler) => {
				listeners[name] = handler;
			},
		});
		const init = vi.fn();

		publicEntry.registerAlpineEngine({ selector: '[data-test-engine]', boundAttr: 'testEngineBound', init });

		// Nothing binds before Alpine signals it is ready
		expect(init).not.toHaveBeenCalled();

		// After the signal, only the unbound element binds, and it is flagged
		listeners['alpine:initialized']();
		expect(init).toHaveBeenCalledTimes(1);
		expect(init).toHaveBeenCalledWith(fresh);
		expect(fresh.dataset.testEngineBound).toBe('1');
	});
});
