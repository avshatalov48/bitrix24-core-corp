import {Sprint} from '../../src/entity/sprint/sprint';
import {ListItems} from '../../src/entity/list.items';

import getInputParams from '../params';

describe('Tasks.Scrum.ListItems', () => {

	const sprintParams = getInputParams('PlannedSprint');

	let sprint = null;
	let listItems = null;

	before(() => {
		sprint = new Sprint(sprintParams);
		listItems = new ListItems(sprint);
	});

	describe('Initialization', () => {
		it('ListItems must be a function', () => {
			assert(typeof ListItems === 'function');
		});
		it('ListItems must be initialized successfully', () => {

		});
	});

	describe('Correct behaviour', () => {
		it('ListItems must be create a dom node', () => {
			const listItemsNode = listItems.render();
			assert(listItemsNode.className === 'tasks-scrum-items-list');
			assert(Number(listItemsNode.dataset.entityId) === sprint.getId());
		});
	});

});