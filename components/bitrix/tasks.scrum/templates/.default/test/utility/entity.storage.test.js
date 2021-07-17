import {EntityStorage} from '../../src/utility/entity.storage';

import {Entity} from '../../src/entity/entity';
import {Backlog} from '../../src/entity/backlog/backlog';
import {Sprint} from '../../src/entity/sprint/sprint';
import {Item} from '../../src/item/item';

import getInputParams from '../params';

describe('Tasks.Scrum.EntityStorage', () => {

	const emptyBacklogParams = getInputParams('EmptyBacklog');
	const activeSprintParams = getInputParams('ActiveSprint');
	const plannedSprintParams = getInputParams('PlannedSprint');
	const completedSprintParams = getInputParams('CompletedSprint');

	const firstItemParams = getInputParams('SimpleItem', 1);
	const secondItemParams = getInputParams('SimpleItem', 2);
	const thirdItemParams = getInputParams('SimpleItem', 3);
	const fourthItemParams = getInputParams('SimpleItem', 4);

	const taskItemParams = getInputParams('Item');

	describe('Initialization', () => {
		it('EntityStorage must be a function', () => {
			assert(typeof EntityStorage === 'function');
		});
		it('EntityStorage must be initialized successfully', () => {
			const entityStorage = new EntityStorage();

			assert.throws(() => entityStorage.getBacklog(), Error, 'EntityStorage: Backlog not found');
			assert(entityStorage.getSprints() instanceof Map);
		});
	});

	describe('Correct behaviour', () => {

		let backlog = null;
		let activeSprint = null;
		let plannedSprint = null;
		let completedSprint = null;

		before(() => {
			backlog = new Backlog(emptyBacklogParams);
			activeSprint = new Sprint(activeSprintParams);
			plannedSprint = new Sprint(plannedSprintParams);
			completedSprint = new Sprint(completedSprintParams);
		});

		it('EntityStorage must be able to add a backlog', () => {
			const entityStorage = new EntityStorage();
			entityStorage.addBacklog(backlog);

			assert.doesNotThrow(() => entityStorage.getBacklog(), Error, 'EntityStorage: Backlog not found');
			assert(entityStorage.getBacklog() instanceof Backlog);
		});
		it('EntityStorage must be able to add/remove sprints', () => {
			const entityStorage = new EntityStorage();
			entityStorage.addSprint(activeSprint);
			entityStorage.addSprint(plannedSprint);
			entityStorage.addSprint(completedSprint);

			let sprints = entityStorage.getSprints();

			assert(sprints.size === 3);
			assert(sprints.has(activeSprint.getId()) === true);
			assert(sprints.has(plannedSprint.getId()) === true);
			assert(sprints.has(completedSprint.getId()) === true);

			entityStorage.removeSprint(activeSprint.getId());
			sprints = entityStorage.getSprints();

			assert(sprints.size === 2);
		});
		it('EntityStorage must be able return sprints for filling', () => {
			const entityStorage = new EntityStorage();
			entityStorage.addSprint(activeSprint);
			entityStorage.addSprint(plannedSprint);
			entityStorage.addSprint(completedSprint);

			const sprints = entityStorage.getSprintsAvailableForFilling(plannedSprint);

			assert(sprints.size === 1);
			assert(sprints.has(activeSprint) === true);
		});
		it('EntityStorage must be able return all entities', () => {
			const entityStorage = new EntityStorage();
			entityStorage.addBacklog(backlog);
			entityStorage.addSprint(activeSprint);
			entityStorage.addSprint(plannedSprint);
			entityStorage.addSprint(completedSprint);

			const entities = entityStorage.getAllEntities();

			assert(entities instanceof Map);
			assert(entities.size === 4);
			assert(entities.has(backlog.getId()) === true);
			assert(entities.has(activeSprint.getId()) === true);
			assert(entities.has(plannedSprint.getId()) === true);
			assert(entities.has(completedSprint.getId()) === true);
		});
		it('EntityStorage must be able find entity by entity id', () => {
			const entityStorage = new EntityStorage();
			entityStorage.addBacklog(backlog);
			entityStorage.addSprint(activeSprint);

			const foundBacklog = entityStorage.findEntityByEntityId(backlog.getId());
			const foundSprint = entityStorage.findEntityByEntityId(activeSprint.getId());

			assert(foundBacklog.getId() === backlog.getId());
			assert(foundSprint.getId() === activeSprint.getId());
		});
		it('EntityStorage must be able manipulate items', () => {
			const entityStorage = new EntityStorage();

			const firstItem = new Item(firstItemParams);
			const secondItem = new Item(secondItemParams);
			const thirdItem = new Item(thirdItemParams);
			const fourthItem = new Item(fourthItemParams);

			const taskItem = new Item(taskItemParams);

			backlog.setItem(firstItem);
			activeSprint.setItem(secondItem);
			plannedSprint.setItem(thirdItem);
			completedSprint.setItem(fourthItem);

			backlog.setItem(taskItem);

			entityStorage.addBacklog(backlog);
			entityStorage.addSprint(activeSprint);
			entityStorage.addSprint(plannedSprint);
			entityStorage.addSprint(completedSprint);

			const allItems = entityStorage.getAllItems();

			assert(allItems instanceof Map);
			assert(allItems.size === 5);
			assert(allItems.has(taskItem.getItemId()) === true);
			assert(allItems.has(firstItem.getItemId()) === true);
			assert(allItems.has(secondItem.getItemId()) === true);
			assert(allItems.has(thirdItem.getItemId()) === true);
			assert(allItems.has(fourthItem.getItemId()) === true);

			const foundFirstItem = entityStorage.findItemByItemId(firstItem.getItemId());

			assert(foundFirstItem instanceof Item);
			assert(foundFirstItem.getItemId() === firstItem.getItemId());

			const foundActiveSprint = entityStorage.findEntityByItemId(secondItem.getItemId());

			assert(foundActiveSprint instanceof Entity);
			assert(foundActiveSprint.getId() === activeSprint.getId());

			const foundTaskItem = entityStorage.findItemBySourceId(taskItem.getSourceId());

			assert(foundTaskItem instanceof Item);
			assert(foundTaskItem.getItemId() === taskItem.getItemId());
		});

	});

});