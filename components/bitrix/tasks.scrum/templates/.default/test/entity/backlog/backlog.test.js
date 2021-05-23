import {Backlog} from '../../../src/entity/backlog/backlog';

import getInputParams from '../../params';

describe('Tasks.Scrum.Backlog', () => {

	let backlog = null;

	const emptyBacklogParams = getInputParams('EmptyBacklog');

	before(() => {
		backlog = new Backlog(emptyBacklogParams);
	});

	describe('Initialization', () => {
		it('Backlog must be a function', () => {
			assert(typeof Backlog === 'function');
		});
		it('Backlog must be initialized successfully', () => {
			assert(backlog.getId() === emptyBacklogParams.id);
		});
	});

	describe('Correct behaviour', () => {
		it('Backlog must be return correct entity type', () => {
			assert(backlog.getEntityType() === 'backlog');
		});
	});
});