import {Item} from '../../src/item/item';

import getInputParams from '../params';

describe('Tasks.Scrum.Item', () => {

	const itemParams = getInputParams('Item');

	let item = null;
	before(() => {
		item = new Item(itemParams);
	})

	describe('Initialization', () => {
		it('Item must be a function', () => {
			assert(typeof Item === 'function');
		});
		it('Item must be initialized successfully', () => {
			assert(item.getItemId() === itemParams.itemId);
			assert(item.getName() === itemParams.name);
			assert(item.getItemType() === itemParams.itemType);
			assert(item.getSort() === itemParams.sort);
			assert(item.getEntityId() === itemParams.entityId);
			assert(item.getEntityType() === itemParams.entityType);
			assert(item.getParentId() === itemParams.parentId);
			assert(item.getSourceId() === itemParams.sourceId);
			assert(item.getParentSourceId() === itemParams.parentSourceId);
			assert(item.getResponsible() === itemParams.responsible);
			assert(item.getStoryPoints().getPoints() === itemParams.storyPoints);

			assert(item.isNodeCreated() === false);
			assert(item.isCompleted() === false);
			assert(item.isDisabled() === false);
			assert(item.isShowIndicators() === false);
		});
	});

	describe('Correct behaviour', () => {
		it('Item can be made movable and non-movable', () => {
			item.setMoveActivity(true);
			assert(item.isMovable() === true);
			item.setMoveActivity(false);
			assert(item.isMovable() === false);
		});
		it('Item can be made disabled', () => {
			item.setDisableStatus(true);
			assert(item.isDisabled() === true);
		});
		it('A parent entity can be set to the item', () => {
			item.setParentEntity(2, 'sprint');
			assert(item.getEntityId() === 2);
			assert(item.getEntityType() === 'sprint');
		});
		it('Item must be able to create its own node element', () => {
			const itemNode = item.render();
			assert(itemNode.className === 'tasks-scrum-item');
			assert(Number(itemNode.dataset.itemId) === item.getItemId());
			assert(Number(itemNode.dataset.sort) === item.getSort());
		});
		it('Item must be able to remove yourself', () => {
			item.removeYourself();
			assert(item.getItemNode() === null);
		});
	});
});