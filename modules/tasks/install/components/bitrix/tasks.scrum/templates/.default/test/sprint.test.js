import {Sprint} from '../src/entity/sprint/sprint';
import {Item} from '../src/item/item';

import loadMessages from './load-messages';

describe('Tasks.Scrum.Sprint', () => {

	loadMessages(__dirname + '../../lang/', 'en', 'header.php');

	describe('Planned sprint', () => {
		const sprintParams = {
			id: 1,
			name: 'Sprint name',
			sort: 1,
			dateStart: 1596723266,
			dateEnd: 1597881600,
			defaultSprintDuration: 604800,
			totalStoryPoints: 3,
			status: 'planned'
		};

		let sprint = null;
		let item = null;
		before(() => {
			sprint = new Sprint(sprintParams);
			item = new Item({
				itemId: 1,
				name: 'Item name',
				sort: 1,
				storyPoints: '3',
			});
		});

		describe('Initialization', () => {
			it('Sprint must be a function', () => {
				assert(typeof Sprint === 'function');
			});
			it('Sprint must be initialized successfully', () => {
				assert(sprint.getId() === sprintParams.id);
				assert(sprint.getName() === sprintParams.name);
				assert(sprint.getSort() === sprintParams.sort);
				assert(sprint.getDateStart() === sprintParams.dateStart);
				assert(sprint.getDateEnd() === sprintParams.dateEnd);
				assert(sprint.getDefaultSprintDuration() === sprintParams.defaultSprintDuration);
			});
		});

		describe('Correct behaviour', () => {
			it('Sprint must be return correct entity type', () => {
				assert(sprint.getEntityType() === 'sprint');
			});
			it('Sprint must be be with planned status', () => {
				assert(sprint.isPlanned() === true);
			});
			it('Sprint must be have an input field', () => {
				assert(sprint.hasInput() === true);
			});
			it('Sprint must be create a dom element', () => {
				const sprintNode = sprint.render();
				assert(sprintNode.className === 'tasks-scrum-sprint');
				assert(Number(sprintNode.dataset.sprintId) === sprint.getId());
				assert(Number(sprintNode.dataset.sprintSort) === sprint.getSort());
			});
			it('Sprint must be able to add an item and to remove an item', () => {
				assert(sprint.getItems().size === 0);
				const itemStoryPoints = parseFloat(item.getStoryPoints().getPoints());
				sprint.setItem(item);
				assert(sprint.getItems().size === 1);
				assert(sprint.getStoryPoints().getPoints() === String(itemStoryPoints));
				sprint.removeItem(item);
				assert(sprint.getItems().size === 0);
				assert(sprint.getStoryPoints().getPoints() === '');
			});
			it('Sprint must be able to remove yourself', () => {
				const eventName = 'removeSprint';
				const listener = sinon.stub();
				sprint.subscribe(eventName, listener);
				sprint.removeYourself();
				assert(listener.callCount === 1);
				assert(sprint.getNode() === null);
			});
		});
	});

});
