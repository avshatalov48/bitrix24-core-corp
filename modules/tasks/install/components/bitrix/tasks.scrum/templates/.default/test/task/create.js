import {Item} from '../../src/item/item';

import getInputParams from '../params';

describe('Creating a task', () => {

	const itemParams = getInputParams('CreateItem');

	let item = null;
	before(() => {
		item = new Item(itemParams);
	});

	describe('Initialization', () => {
		it('Item object must be a function', () => {
			assert(typeof Item === 'function');
		});
	});
});