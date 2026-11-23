// @vitest-environment jsdom

import Alpine from '@alpinejs/csp';
import { beforeAll, describe, expect, it } from 'vitest';

import { registerCheckboxRequiredGroup } from './checkbox-required-group.js';

/**
 * The real component against the real `@alpinejs/csp` build: a required Checkboxes group carries
 * `required` on every box until one is checked, then on none, and again on all when none are.
 */
beforeAll(() => {
	window.Alpine = Alpine;
	registerCheckboxRequiredGroup(Alpine);
	Alpine.start();
});

/**
 * Mount a group of three boxes, optionally with some prechecked, and let Alpine initialize it.
 *
 * @param {string[]} checked The values already checked.
 * @returns {HTMLInputElement[]} The three boxes.
 */
function mount(checked = []) {
	document.body.innerHTML = `
		<div x-data="checkboxRequiredGroup" @change="sync">
			${['a', 'b', 'c']
				.map((value) => `<input type="checkbox" value="${value}" required ${checked.includes(value) ? 'checked' : ''}>`)
				.join('')}
		</div>`;
	Alpine.initTree(document.body);

	return [...document.querySelectorAll('input')];
}

describe('the checkboxRequiredGroup component', () => {
	it('keeps every box required while none is checked', () => {
		// A fresh group with nothing checked
		const boxes = mount();

		// Expect all three still required
		expect(boxes.map((box) => box.required)).toEqual([true, true, true]);
	});

	it('clears required from the whole group once one box is checked, and restores it when none are', () => {
		// A fresh group; check one box and let the wrapper's change handler run
		const boxes = mount();
		boxes[1].checked = true;
		boxes[1].dispatchEvent(new Event('change', { bubbles: true }));

		// Expect no box required
		expect(boxes.map((box) => box.required)).toEqual([false, false, false]);

		// Uncheck it again
		boxes[1].checked = false;
		boxes[1].dispatchEvent(new Event('change', { bubbles: true }));

		// Expect every box required once more
		expect(boxes.map((box) => box.required)).toEqual([true, true, true]);
	});

	it('clears required on init for a prefilled group', () => {
		// A group re-rendered after a server validation error, with one box already checked
		const boxes = mount(['c']);

		// Expect the requirement already satisfied
		expect(boxes.map((box) => box.required)).toEqual([false, false, false]);
	});

	it('keeps two groups on one page isolated', () => {
		// Two groups, one prechecked
		document.body.innerHTML = `
			<div id="one" x-data="checkboxRequiredGroup" @change="sync"><input type="checkbox" required checked></div>
			<div id="two" x-data="checkboxRequiredGroup" @change="sync"><input type="checkbox" required></div>`;
		Alpine.initTree(document.body);

		// Expect only the unchecked group to stay required
		expect(document.querySelector('#one input').required).toBe(false);
		expect(document.querySelector('#two input').required).toBe(true);
	});
});
