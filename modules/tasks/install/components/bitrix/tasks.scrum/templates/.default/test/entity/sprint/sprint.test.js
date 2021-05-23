import {Sprint} from '../../../src/entity/sprint/sprint';
import {StoryPoints} from '../../../src/utility/story.points';
import {ListItems} from '../../../src/entity/list.items';
import {Item} from '../../../src/item/item';

import loadMessages from '../../load-messages';
import getInputParams from '../../params';

describe('Tasks.Scrum.Sprint', () => {

	loadMessages(__dirname + '../../../../lang/', 'en', 'header.php');

	describe('Active sprint', () => {
		let sprint = null;

		const sprintParams = getInputParams('ActiveSprint');
		const simpleItemParams = getInputParams('SimpleItem');

		before(() => {
			sprint = new Sprint(sprintParams);
			sprint.addListItems(new ListItems(sprint));
		});

		describe('Initialization', () => {
			it('Sprint must be a function', () => {
				assert(typeof Sprint === 'function');
			});
		});

		describe('Correct behaviour', () => {
			it('Sprint must be return correct entity type', () => {
				assert(sprint.getEntityType() === 'sprint');
			});
			it('Sprint must be be with active status', () => {
				assert(sprint.isActive() === true);
			});
			it('Sprint must be correctly update its visibility when base filtering', () => {
				sprint.setExactSearchApplied(false);
				sprint.render();
				sprint.updateVisibility();
				assert(sprint.getNode().style.display === 'block');
				assert(sprint.getContentNode().style.display === 'block');
			});
			it('Sprint must be correctly update its visibility when exact filtering', () => {
				sprint.setExactSearchApplied(true);
				sprint.render();
				sprint.updateVisibility();
				assert(sprint.getNode().style.display === 'none');
				assert(sprint.getContentNode().style.display === 'block');
				sprint.setItem(new Item(simpleItemParams));
				sprint.updateVisibility();
				assert(sprint.getNode().style.display === 'block');
				assert(sprint.getContentNode().style.display === 'block');
			});
		});
	});

	describe('Planned sprint', () => {

		let sprint = null;

		const sprintParams = getInputParams('PlannedSprint');
		const simpleItemParams = getInputParams('SimpleItem');

		before(() => {
			sprint = new Sprint(sprintParams);
			sprint.addListItems(new ListItems(sprint));
		});

		describe('Initialization', () => {
			it('Sprint must be a function', () => {
				assert(typeof Sprint === 'function');
			});
			it('Sprint must be initialized successfully', () => {
				const totalStoryPoints = new StoryPoints();
				totalStoryPoints.addPoints(sprintParams.totalStoryPoints);
				const totalCompletedStoryPoints = new StoryPoints();
				totalCompletedStoryPoints.addPoints(sprintParams.totalCompletedStoryPoints);
				const totalUncompletedStoryPoints = new StoryPoints();
				totalUncompletedStoryPoints.addPoints(sprintParams.totalUncompletedStoryPoints);

				assert(sprint.getId() === sprintParams.id);
				assert(sprint.getName() === sprintParams.name);
				assert(sprint.getSort() === sprintParams.sort);
				assert(sprint.getDateStart() === sprintParams.dateStart);
				assert(sprint.getDateEnd() === sprintParams.dateEnd);
				assert(sprint.getWeekendDaysTime() === sprintParams.weekendDaysTime);
				assert(sprint.getDefaultSprintDuration() === sprintParams.defaultSprintDuration);
				assert(sprint.getStatus() === sprintParams.status);
				assert(sprint.getTotalStoryPoints().getPoints() === totalStoryPoints.getPoints());
				assert(sprint.getTotalCompletedStoryPoints().getPoints() === totalCompletedStoryPoints.getPoints());
				assert(sprint.getTotalUncompletedStoryPoints().getPoints() === totalUncompletedStoryPoints.getPoints());
				assert(sprint.getCompletedTasks() === sprintParams.completedTasks);
				assert(sprint.getUncompletedTasks() === sprintParams.uncompletedTasks);
				assert(sprint.getItems().size === 0);
				assert(sprint.getInfo() === sprintParams.info);
				assert(sprint.getListItems() instanceof ListItems);
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
			it('Sprint must be able to remove yourself', () => {
				const eventName = 'removeSprint';
				const listener = sinon.stub();
				sprint.subscribe(eventName, listener);
				sprint.removeYourself();
				assert(listener.callCount === 1);
				assert(sprint.getNode() === null);
			});
			it('Sprint must be return correct story points type', () => {
				assert(sprint.getTotalCompletedStoryPoints() instanceof StoryPoints);
				assert(sprint.getTotalUncompletedStoryPoints() instanceof StoryPoints);
			});
			it('Sprint must be correctly update its visibility when base filtering', () => {
				sprint.setExactSearchApplied(false);
				sprint.render();
				sprint.updateVisibility();
				assert(sprint.getNode().style.display === 'block');
				assert(sprint.getContentNode().style.display === 'block');
			});
			it('Sprint must be correctly update its visibility when exact filtering', () => {
				sprint.setExactSearchApplied(true);
				sprint.render();
				sprint.updateVisibility();
				assert(sprint.getNode().style.display === 'none');
				assert(sprint.getContentNode().style.display === 'block');
				sprint.setItem(new Item(simpleItemParams));
				sprint.updateVisibility();
				assert(sprint.getNode().style.display === 'block');
				assert(sprint.getContentNode().style.display === 'block');
			});
		});
	});

	describe('Completed sprint', () => {
		let sprint = null;

		const sprintParams = getInputParams('CompletedSprint');
		const simpleItemParams = getInputParams('SimpleItem');

		before(() => {
			sprint = new Sprint(sprintParams);
			sprint.addListItems(new ListItems(sprint));
		});

		describe('Initialization', () => {
			it('Sprint must be a function', () => {
				assert(typeof Sprint === 'function');
			});
		});

		describe('Correct behaviour', () => {
			it('Sprint must be return correct entity type', () => {
				assert(sprint.getEntityType() === 'sprint');
			});
			it('Sprint must be be with completed status', () => {
				assert(sprint.isCompleted() === true);
			});
			it('Sprint must be correctly update its visibility when base filtering', () => {
				sprint.setExactSearchApplied(false);
				sprint.render();
				sprint.updateVisibility();
				assert(sprint.getNode().style.display === 'block');
				assert(sprint.getContentNode().style.display === 'none');
			});
			it('Sprint must be correctly update its visibility when exact filtering', () => {
				sprint.setExactSearchApplied(true);
				sprint.render();
				sprint.updateVisibility();
				assert(sprint.getNode().style.display === 'none');
				assert(sprint.getContentNode().style.display === 'none');
				sprint.setItem(new Item(simpleItemParams));
				sprint.updateVisibility();
				assert(sprint.getNode().style.display === 'block');
				assert(sprint.getContentNode().style.display === 'block');
			});
		});
	});

});
