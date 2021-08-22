import {Entity} from '../../src/entity/entity';
import {Item} from '../../src/item/item';
import {StoryPoints} from '../../src/utility/story.points';

import getInputParams from '../params';

describe('Tasks.Scrum.Entity', () => {

	let entity = null;
	let item = null;

	const emptyEntityParams = getInputParams('EmptyEntity');
	const simpleItemParams = getInputParams('SimpleItem');

	before(() => {
		entity = new Entity(emptyEntityParams);
		item = new Item(simpleItemParams);
	});

	describe('Initialization', () => {
		it('Entity must be a function', () => {
			assert(typeof Entity === 'function');
		});
		it('Entity must be initialized successfully', () => {
			assert(entity.getId() === emptyEntityParams.id);
		});
	});

	describe('Correct behaviour', () => {
		it('Entity must be return correct entity type', () => {
			assert(entity.getEntityType() === 'entity');
		});
		it('Entity must not be an active', () => {
			assert(entity.isActive() === false);
		});
		it('Entity must not be a completed', () => {
			assert(entity.isCompleted() === false);
		});
		it('Entity must not be a group mode', () => {
			assert(entity.isGroupMode() === false);
		});
		it('Entity must be have an input field', () => {
			assert(entity.hasInput() === true);
		});
		it('Entity must not be in exact search status', () => {
			assert(entity.isExactSearchApplied() === false);
		});
		it('Entity must be be able to set or unset yourself filterable status', () => {
			entity.setExactSearchApplied(true);
			assert(entity.isExactSearchApplied() === true);
			entity.setExactSearchApplied(false);
			assert(entity.isExactSearchApplied() === false);
		});
		it('Entity must be able to add and remove an item', () => {
			assert(entity.getItems().size === 0);
			entity.setItem(item);
			assert(entity.getItems().size === 1);
			entity.removeItem(item);
			assert(entity.getItems().size === 0);
		});
		it('Entity must be able return an item by id', () => {
			entity.setItem(item);
			const returnedItem = entity.getItemByItemId(simpleItemParams.itemId);
			assert(returnedItem instanceof Item);
			assert(returnedItem.getItemId() === simpleItemParams.itemId);
		});
		it('Entity must be return correct story points type', () => {
			assert(entity.getStoryPoints() instanceof StoryPoints);
		});
	});
});