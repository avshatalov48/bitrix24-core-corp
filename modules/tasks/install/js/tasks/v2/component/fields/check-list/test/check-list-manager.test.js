import { Text } from 'main.core';

import 'tasks.v2.test';
import type { CheckListModel } from 'tasks.v2.model.check-list';

import { CheckListManager } from '../src/lib/check-list-manager';

import {
	createCheckListItem,
	createDeepNestedGroupModeStructure,
	createDemoCheckLists,
	createGroupModeCheckLists,
} from './check-list-data';

describe('CheckListManager', () => {
	it('Should be a function', () => {
		assert(typeof CheckListManager === 'function');
	});

	describe('Create without data', () => {
		let manager;

		beforeEach(() => {
			manager = new CheckListManager();
		});

		it('Should does not throws if data is not object', () => {
			assert.doesNotThrow(() => {
				manager = new CheckListManager();
			});
		});

		describe('getItemLevel', () => {
			it('should return 0 level when checkLists is not defined', () => {
				const mockItem = createCheckListItem();

				const level = manager.getItemLevel(mockItem);

				assert.equal(level, 0);
			});

			it('should handle item with parentId 0', () => {
				const mockItem = createCheckListItem();

				const level = manager.getItemLevel(mockItem);

				assert.equal(level, 0);
			});

			it('should return 0 when parent item not found', () => {
				const notExistentParentId = 999;
				const mockItem = createCheckListItem(notExistentParentId);

				const level = manager.getItemLevel(mockItem);

				assert.equal(level, 0);
			});
		});
	});

	describe('Create with data', () => {
		let manager;
		let demoCheckLists;

		beforeEach(() => {
			demoCheckLists = createDemoCheckLists();
			manager = new CheckListManager({
				computed: {
					checkLists: () => demoCheckLists,
				},
			});
		});

		describe('getItemLevel', () => {
			it('should return 0 for root items', () => {
				const rootItem = demoCheckLists.find(item => item.parentId === 0);
				const level = manager.getItemLevel(rootItem);

				assert.equal(level, 0);
			});

			it('should return 1 for direct children of root', () => {
				const rootItem = demoCheckLists.find(item => item.parentId === 0);
				const childItem = demoCheckLists.find(item => item.parentId === rootItem.id);

				const level = manager.getItemLevel(childItem);

				assert.equal(level, 1);
			});

			it('should return correct level for nested items', () => {
				const rootItem = demoCheckLists.find(item => item.parentId === 0);
				const childItem = demoCheckLists.find(item => item.parentId === rootItem.id);
				const nestedItem = demoCheckLists.find(item => item.parentId === childItem.id);

				if (nestedItem)
				{
					const level = manager.getItemLevel(nestedItem);

					assert.equal(level, 2);
				}
				else
				{
					assert.fail('Nested item not found in demo data');
				}
			});

			it('should return 0 for item not in the list', () => {
				const externalItem = createCheckListItem(999);
				const level = manager.getItemLevel(externalItem);

				assert.equal(level, 0);
			});

			it('should handle circular references safely', () => {
				const item1 = createCheckListItem();
				const item2 = createCheckListItem(item1.id);

				item1.parentId = item2.id;

				const testManager = new CheckListManager({
					computed: {
						checkLists: () => [item1, item2],
					},
				});

				let level;
				assert.doesNotThrow(() => {
					level = testManager.getItemLevel(item1);
				});

				assert.strictEqual(typeof level, 'number');
				assert(level >= 0);
			});
		});

		describe('isItemDescendant', () => {
			it('should return true for direct child', () => {
				const rootItem = demoCheckLists.find(item => item.parentId === 0);
				const childItem = demoCheckLists.find(item => item.parentId === rootItem.id);

				const isDescendant = manager.isItemDescendant(rootItem, childItem);

				assert.strictEqual(isDescendant, true);
			});

			it('should return true for nested child', () => {
				const rootItem = demoCheckLists.find(item => item.parentId === 0);
				const childItem = demoCheckLists.find(item => item.parentId === rootItem.id);
				const nestedItem = demoCheckLists.find(item => item.parentId === childItem.id);

				if (nestedItem)
				{
					const isDescendant = manager.isItemDescendant(rootItem, nestedItem);

					assert.strictEqual(isDescendant, true);
				}
				else
				{
					assert.fail('Nested item not found in demo data');
				}
			});

			it('should return false for root items', () => {
				const rootItems = demoCheckLists.filter(item => item.parentId === 0);
				const isDescendant = manager.isItemDescendant(rootItems[0], rootItems[1]);

				assert.strictEqual(isDescendant, false);
			});

			it('should return false for unrelated items', () => {
				const rootItems = demoCheckLists.filter(item => item.parentId === 0);
				const childFromFirstRoot = demoCheckLists.find(item => item.parentId === rootItems[0].id);
				const childFromSecondRoot = demoCheckLists.find(item => item.parentId === rootItems[1].id);

				const isDescendant = manager.isItemDescendant(childFromFirstRoot, childFromSecondRoot);

				assert.strictEqual(isDescendant, false);
			});

			it('should return false when potential ancestor is null', () => {
				const childItem = demoCheckLists.find(item => item.parentId !== 0);
				const isDescendant = manager.isItemDescendant(null, childItem);

				assert.strictEqual(isDescendant, false);
			});

			it('should return false when item is null', () => {
				const rootItem = demoCheckLists.find(item => item.parentId === 0);
				const isDescendant = manager.isItemDescendant(rootItem, null);

				assert.strictEqual(isDescendant, false);
			});

			it('should handle circular references safely', () => {
				const item1 = createCheckListItem();
				const item2 = createCheckListItem(item1.id);
				item1.parentId = item2.id;

				const testManager = new CheckListManager({
					computed: {
						checkLists: () => [item1, item2],
					},
				});

				assert.doesNotThrow(() => {
					const result = testManager.isItemDescendant(item1, item2);

					assert.strictEqual(typeof result, 'boolean');
				});
			});
		});

		describe('syncParentCompletionState', () => {
			let updateCalls;
			let mockUpdateFn;

			beforeEach(() => {
				updateCalls = [];
				mockUpdateFn = (id, fields) => {
					updateCalls.push({ id, fields });
				};
			});

			it('should not update when item has no parent and no parentItemId provided', () => {
				const rootItem = demoCheckLists.find(item => item.parentId === 0);
				manager.syncParentCompletionState(rootItem.id, mockUpdateFn);

				assert.strictEqual(updateCalls.length, 0);
			});

			it('should mark parent as complete when all children are complete', () => {
				const parentId = Text.getRandom();
				const child1 = createCheckListItem(parentId, { isComplete: true });
				const child2 = createCheckListItem(parentId, { isComplete: true });
				const parent = createCheckListItem(0, { id: parentId, isComplete: false });

				const testManager = new CheckListManager({
					computed: {
						checkLists: () => [parent, child1, child2],
					},
				});

				testManager.syncParentCompletionState(child1.id, mockUpdateFn);

				assert.strictEqual(updateCalls.length, 1);
				assert.strictEqual(updateCalls[0].id, parentId);
				assert.strictEqual(updateCalls[0].fields.isComplete, true);
			});

			it('should mark parent as incomplete when some children are incomplete', () => {
				const parentId = Text.getRandom();
				const child1 = createCheckListItem(parentId, { isComplete: true });
				const child2 = createCheckListItem(parentId, { isComplete: false });
				const parent = createCheckListItem(0, { id: parentId, isComplete: true });

				const testManager = new CheckListManager({
					computed: {
						checkLists: () => [parent, child1, child2],
					},
				});

				testManager.syncParentCompletionState(child1.id, mockUpdateFn);

				assert.strictEqual(updateCalls.length, 1);
				assert.strictEqual(updateCalls[0].id, parentId);
				assert.strictEqual(updateCalls[0].fields.isComplete, false);
			});

			it('should update grandparent when parent state changes', () => {
				const grandparentId = Text.getRandom();
				const parentId = Text.getRandom();
				const childId = Text.getRandom();

				let grandparent = createCheckListItem(0, { id: grandparentId, isComplete: false });
				let parent = createCheckListItem(grandparentId, { id: parentId, isComplete: false });
				const child = createCheckListItem(parentId, { id: childId, isComplete: true });

				const testList = [grandparent, parent, child];

				const testManager = new CheckListManager({
					computed: {
						checkLists: () => testList,
					},
				});

				const realTimeUpdateFn = (id, fields) => {
					const item = testList.find((i: CheckListModel) => i.id === id);
					if (item)
					{
						Object.assign(item, fields);
					}
					mockUpdateFn(id, fields);
				};

				testManager.syncParentCompletionState(childId, realTimeUpdateFn);

				assert.strictEqual(updateCalls.length, 2);
				assert.strictEqual(updateCalls[0].id, parentId);
				assert.strictEqual(updateCalls[0].fields.isComplete, true);
				assert.strictEqual(updateCalls[1].id, grandparentId);
				assert.strictEqual(updateCalls[1].fields.isComplete, true);
			});

			it('should handle empty parent case', () => {
				const parentId = Text.getRandom();
				const parent = createCheckListItem(0, { id: parentId, isComplete: true });

				const testManager = new CheckListManager({
					computed: {
						checkLists: () => [parent],
					},
				});

				testManager.syncParentCompletionState('non-existent', mockUpdateFn, parentId);

				assert.strictEqual(updateCalls.length, 1);
				assert.strictEqual(updateCalls[0].id, parentId);
				assert.strictEqual(updateCalls[0].fields.isComplete, false);
			});

			it('should not update when parent state is already correct', () => {
				const parentId = Text.getRandom();
				const child1 = createCheckListItem(parentId, { isComplete: true });
				const child2 = createCheckListItem(parentId, { isComplete: true });
				const parent = createCheckListItem(0, { id: parentId, isComplete: true });

				const testManager = new CheckListManager({
					computed: {
						checkLists: () => [parent, child1, child2],
					},
				});

				testManager.syncParentCompletionState(child1.id, mockUpdateFn);

				assert.strictEqual(updateCalls.length, 0);
			});

			it('should handle null/undefined inputs safely', () => {
				assert.doesNotThrow(() => {
					manager.syncParentCompletionState(null, mockUpdateFn);
					manager.syncParentCompletionState(undefined, mockUpdateFn);
				});
			});
		});

		describe('getChildren', () => {
			it('should return empty array when no children exist', () => {
				const result = manager.getChildren('non-existent-id');

				assert.strictEqual(result.length, 0);
			});

			it('should return direct children for root item', () => {
				const rootItem = demoCheckLists.find(item => item.parentId === 0);
				const result = manager.getChildren(rootItem.id);

				assert.strictEqual(result.length, 2);
				assert(result.every((child: CheckListModel) => child.parentId === rootItem.id));
			});

			it('should return direct children for nested item', () => {
				const parentItem = demoCheckLists.find((item: CheckListModel) => item.title.includes('Child of'));
				const result = manager.getChildren(parentItem.id);

				assert.strictEqual(result.length, 1);
				assert(result[0].title.includes('Nested under'));
			});
		});

		describe('getSiblings', () => {
			it('should not include the item itself', () => {
				const item = demoCheckLists[0];
				const result = manager.getSiblings(item.id, item.parentId);

				assert(!result.some((sibling: CheckListModel) => sibling.id === item.id));
			});

			it('should return empty array when item has no siblings', () => {
				const nestedItem = demoCheckLists.find((item: CheckListModel) =>
					demoCheckLists.filter((sibling: CheckListModel) => sibling.parentId === item.parentId).length === 1
				);
				const result = manager.getSiblings(nestedItem.id, nestedItem.parentId);

				assert.strictEqual(result.length, 0);
			});

			it('should return all siblings for root items', () => {
				const rootItems = demoCheckLists.filter(item => item.parentId === 0);
				const testItem = rootItems[0];
				const result = manager.getSiblings(testItem.id, testItem.parentId);

				assert.strictEqual(result.length, rootItems.length - 1);
				assert(result.every(item => item.parentId === 0));
			});

			it('should handle undefined item', () => {
				assert.doesNotThrow(() => {
					const result = manager.getSiblings();

					assert.strictEqual(result.length, 0);
				});
			});
		});

		describe('resortItemsOnLevel', () => {
			let mockUpdateFn;
			let updateCalls;

			beforeEach(() => {
				updateCalls = [];
				mockUpdateFn = (updates) => {
					updateCalls.push(...updates);
				};
			});

			it('should renumber all items on level starting from 0', () => {
				const items = [
					createCheckListItem(0, { id: 'item1', sortIndex: 2 }),
					createCheckListItem(0, { id: 'item2', sortIndex: 0 }),
					createCheckListItem(0, { id: 'item3', sortIndex: 5 })
				];

				const manager = new CheckListManager({
					computed: { checkLists: () => items }
				});

				manager.resortItemsOnLevel(0, mockUpdateFn);

				assert.strictEqual(updateCalls.length, 3);

				const updatedItems = updateCalls.sort((a, b) => a.sortIndex - b.sortIndex);
				assert.strictEqual(updatedItems[0].id, 'item2');
				assert.strictEqual(updatedItems[0].sortIndex, 0);
				assert.strictEqual(updatedItems[1].id, 'item1');
				assert.strictEqual(updatedItems[1].sortIndex, 1);
				assert.strictEqual(updatedItems[2].id, 'item3');
				assert.strictEqual(updatedItems[2].sortIndex, 2);
			});

			it('should handle items with equal sortIndex', () => {
				const items = [
					createCheckListItem(0, { id: 'item1', sortIndex: 1 }),
					createCheckListItem(0, { id: 'item2', sortIndex: 1 }),
					createCheckListItem(0, { id: 'item3', sortIndex: 2 })
				];

				const manager = new CheckListManager({
					computed: { checkLists: () => items }
				});

				manager.resortItemsOnLevel(0, mockUpdateFn);

				assert.strictEqual(updateCalls.length, 3);

				const uniqueIndexes = new Set(updateCalls.map(u => u.sortIndex));
				assert.strictEqual(uniqueIndexes.size, 3);
			});

			it('should handle empty level', () => {
				const manager = new CheckListManager({
					computed: { checkLists: () => [] }
				});

				assert.doesNotThrow(() => {
					manager.resortItemsOnLevel('non-existent', 0, mockUpdateFn);
				});
				assert.strictEqual(updateCalls.length, 0);
			});

			it('should work with nested levels', () => {
				const parent = createCheckListItem(0, { id: 'parent' });
				const children = [
					createCheckListItem('parent', { id: 'child1', sortIndex: 5 }),
					createCheckListItem('parent', { id: 'child2', sortIndex: 3 })
				];

				const manager = new CheckListManager({
					computed: { checkLists: () => [parent, ...children] }
				});

				manager.resortItemsOnLevel('parent', mockUpdateFn);

				assert.strictEqual(updateCalls.length, 2);
				const updatedChild1 = updateCalls.find(u => u.id === 'child1');
				const updatedChild2 = updateCalls.find(u => u.id === 'child2');

				assert.strictEqual(updatedChild2.sortIndex, 0);
				assert.strictEqual(updatedChild1.sortIndex, 1);
			});

			it('should maintain relative order of items', () => {
				const items = [
					createCheckListItem(0, { id: 'item1', sortIndex: 10, title: 'A' }),
					createCheckListItem(0, { id: 'item2', sortIndex: 20, title: 'B' }),
					createCheckListItem(0, { id: 'item3', sortIndex: 30, title: 'C' })
				];

				const manager = new CheckListManager({
					computed: { checkLists: () => items }
				});

				manager.resortItemsOnLevel(0, mockUpdateFn);

				const sortedUpdates = [...updateCalls].sort((a, b) => a.sortIndex - b.sortIndex);
				assert.strictEqual(sortedUpdates[0].title, 'A');
				assert.strictEqual(sortedUpdates[1].title, 'B');
				assert.strictEqual(sortedUpdates[2].title, 'C');
			});
		});

		describe('resortItemsAfterIndex', () => {
			let mockUpdateFn;
			let updateCalls;

			beforeEach(() => {
				updateCalls = [];
				mockUpdateFn = (updates) => {
					updateCalls.push(...updates);
				};
			});

			it('should increment sortIndex by 1 for items >= specified index', () => {
				const items = [
					createCheckListItem(0, { id: 'item1', sortIndex: 0 }),
					createCheckListItem(0, { id: 'item2', sortIndex: 1 }),
					createCheckListItem(0, { id: 'item3', sortIndex: 2 }),
					createCheckListItem(0, { id: 'item4', sortIndex: 3 }),
				];

				const manager = new CheckListManager({
					computed: { checkLists: () => items }
				});

				manager.resortItemsAfterIndex(0, 2, mockUpdateFn);

				assert.strictEqual(updateCalls.length, 2);
				assert.strictEqual(updateCalls[0].id, 'item3');
				assert.strictEqual(updateCalls[0].sortIndex, 3);
				assert.strictEqual(updateCalls[1].id, 'item4');
				assert.strictEqual(updateCalls[1].sortIndex, 4);
			});

			it('should handle empty items list', () => {
				const manager = new CheckListManager({
					computed: { checkLists: () => [] }
				});

				manager.resortItemsAfterIndex(0, 1, mockUpdateFn);

				assert.strictEqual(updateCalls.length, 0);
			});

			it('should not update if no items match condition', () => {
				const items = [
					createCheckListItem(0, { id: 'item1', sortIndex: 0 }),
					createCheckListItem(0, { id: 'item2', sortIndex: 1 }),
				];

				const manager = new CheckListManager({
					computed: { checkLists: () => items }
				});

				manager.resortItemsAfterIndex(0, 2, mockUpdateFn);
				assert.strictEqual(updateCalls.length, 0);
			});

			it('should work with nested levels', () => {
				const parent = createCheckListItem(0, { id: 'parent' });
				const children = [
					createCheckListItem('parent', { id: 'child1', sortIndex: 0 }),
					createCheckListItem('parent', { id: 'child2', sortIndex: 1 }),
					createCheckListItem('parent', { id: 'child3', sortIndex: 2 }),
				];

				const manager = new CheckListManager({
					computed: { checkLists: () => [parent, ...children] }
				});

				manager.resortItemsAfterIndex('parent', 1, mockUpdateFn);

				assert.strictEqual(updateCalls.length, 2);
				assert.strictEqual(updateCalls[0].id, 'child2');
				assert.strictEqual(updateCalls[0].sortIndex, 2);
				assert.strictEqual(updateCalls[1].id, 'child3');
				assert.strictEqual(updateCalls[1].sortIndex, 3);
			});

			it('should maintain original order', () => {
				const items = [
					createCheckListItem(0, { id: 'item1', sortIndex: 0, title: 'A' }),
					createCheckListItem(0, { id: 'item2', sortIndex: 1, title: 'B' }),
					createCheckListItem(0, { id: 'item3', sortIndex: 2, title: 'C' }),
				];

				const manager = new CheckListManager({
					computed: { checkLists: () => items }
				});

				manager.resortItemsAfterIndex(0, 1, mockUpdateFn);

				assert.strictEqual(updateCalls[0].title, 'B');
				assert.strictEqual(updateCalls[1].title, 'C');
			});

			it('should handle gaps in sortIndex', () => {
				const items = [
					createCheckListItem(0, { id: 'item1', sortIndex: 0 }),
					createCheckListItem(0, { id: 'item2', sortIndex: 1 }),
					createCheckListItem(0, { id: 'item3', sortIndex: 3 }),
				];

				const manager = new CheckListManager({
					computed: { checkLists: () => items }
				});

				manager.resortItemsAfterIndex(0, 1, mockUpdateFn);

				assert.strictEqual(updateCalls.length, 2);
				assert.strictEqual(updateCalls[0].sortIndex, 2);
				assert.strictEqual(updateCalls[1].sortIndex, 4);
			});
		});

		describe('getAllChildren', () => {
			it('should return empty array for item with no children', () => {
				const item = createCheckListItem(0, { id: 'parent' });
				const manager = new CheckListManager({
					computed: { checkLists: () => [item] }
				});

				const result = manager.getAllChildren('parent');
				assert.strictEqual(result.length, 0);
			});

			it('should return direct children for item', () => {
				const parent = createCheckListItem(0, { id: 'parent' });
				const children = [
					createCheckListItem('parent', { id: 'child1' }),
					createCheckListItem('parent', { id: 'child2' })
				];

				const manager = new CheckListManager({
					computed: { checkLists: () => [parent, ...children] }
				});

				const result = manager.getAllChildren('parent');

				assert.strictEqual(result.length, 2);
				assert(result.every((child: CheckListModel) => ['child1', 'child2'].includes(child.id)));
			});

			it('should return all nested children recursively', () => {
				const parent = createCheckListItem(0, { id: 'parent' });
				const child1 = createCheckListItem('parent', { id: 'child1' });
				const child2 = createCheckListItem('parent', { id: 'child2' });
				const grandchild1 = createCheckListItem('child1', { id: 'grandchild1' });
				const grandchild2 = createCheckListItem('child1', { id: 'grandchild2' });

				const manager = new CheckListManager({
					computed: { checkLists: () => [parent, child1, child2, grandchild1, grandchild2] }
				});

				const result = manager.getAllChildren('parent');
				assert.strictEqual(result.length, 4);
				assert(result.some((child: CheckListModel) => child.id === 'child1'));
				assert(result.some((child: CheckListModel) => child.id === 'child2'));
				assert(result.some((child: CheckListModel) => child.id === 'grandchild1'));
				assert(result.some((child: CheckListModel) => child.id === 'grandchild2'));
			});

			it('should return children in correct sort order', () => {
				const parent = createCheckListItem(0, { id: 'parent' });
				const children = [
					createCheckListItem('parent', { id: 'child1', sortIndex: 2 }),
					createCheckListItem('parent', { id: 'child2', sortIndex: 1 }),
					createCheckListItem('parent', { id: 'child3', sortIndex: 0 })
				];

				const manager = new CheckListManager({
					computed: { checkLists: () => [parent, ...children] }
				});

				const result = manager.getAllChildren('parent');

				assert.strictEqual(result.length, 3);
				assert.strictEqual(result[0].id, 'child3');
				assert.strictEqual(result[1].id, 'child2');
				assert.strictEqual(result[2].id, 'child1');
			});

			it('should handle deep nesting correctly', () => {
				const items = [
					createCheckListItem(0, { id: 'level1' }),
					createCheckListItem('level1', { id: 'level2' }),
					createCheckListItem('level2', { id: 'level3' }),
					createCheckListItem('level3', { id: 'level4' })
				];

				const manager = new CheckListManager({
					computed: { checkLists: () => items }
				});

				const result = manager.getAllChildren('level1');
				assert.strictEqual(result.length, 3);
				assert.strictEqual(result[0].id, 'level2');
				assert.strictEqual(result[1].id, 'level3');
				assert.strictEqual(result[2].id, 'level4');
			});

			it('should return empty array for non-existent item', () => {
				const manager = new CheckListManager({
					computed: { checkLists: () => [] }
				});

				const result = manager.getAllChildren('non-existent');

				assert.strictEqual(result.length, 0);
			});

			it('should handle circular references safely', () => {
				const item1 = createCheckListItem(0, { id: 'item1' });
				const item2 = createCheckListItem('item1', { id: 'item2' });
				item1.parentId = 'item2';

				const manager = new CheckListManager({
					computed: { checkLists: () => [item1, item2] }
				});

				assert.doesNotThrow(() => {
					const result = manager.getAllChildren('item1');

					assert.strictEqual(result.length, 1);
					assert.strictEqual(result[0].id, 'item2');

					assert(!result.some((item: CheckListModel) => item.id === 'item1'));
				});
			});

			it('should work with demo checklists structure', () => {
				const demoCheckLists = createDemoCheckLists();
				const manager = new CheckListManager({
					computed: { checkLists: () => demoCheckLists }
				});

				const rootItem = demoCheckLists.find((item: CheckListModel) => item.parentId === 0);
				const result = manager.getAllChildren(rootItem.id);

				assert.strictEqual(result.length, 4);
			});
		});

		describe('moveRight', () => {
			let mockUpdateFn;
			let updateCalls;

			beforeEach(() => {
				updateCalls = [];
				mockUpdateFn = (updates) => {
					updateCalls.push(...updates);
				};
			});

			it('should move item to become child of previous sibling', () => {
				const parent = createCheckListItem(0, { id: 'parent', sortIndex: 0 });
				const item1 = createCheckListItem('parent', { id: 'item1', sortIndex: 0 });
				const item2 = createCheckListItem('parent', { id: 'item2', sortIndex: 1 });

				const manager = new CheckListManager({
					computed: { checkLists: () => [parent, item1, item2] }
				});

				manager.moveRight(item2, mockUpdateFn);

				assert.strictEqual(updateCalls.length, 1);
				assert.strictEqual(updateCalls[0].id, 'item2');
				assert.strictEqual(updateCalls[0].parentId, 'item1');
				assert.strictEqual(updateCalls[0].sortIndex, 0);
			});

			it('should not move root items', () => {
				const rootItem = createCheckListItem(0, { id: 'root' });
				const manager = new CheckListManager({
					computed: { checkLists: () => [rootItem] }
				});

				manager.moveRight(rootItem, mockUpdateFn);
				assert.strictEqual(updateCalls.length, 0);
			});

			it('should not move if no valid previous sibling', () => {
				const parent = createCheckListItem(0, { id: 'parent' });
				const item1 = createCheckListItem('parent', { id: 'item1', sortIndex: 0 });

				const manager = new CheckListManager({
					computed: { checkLists: () => [parent, item1] }
				});

				manager.moveRight(item1, mockUpdateFn);
				assert.strictEqual(updateCalls.length, 0);
			});

			it('should maintain sort order when moving', () => {
				const parent = createCheckListItem(0, { id: 'parent' });
				const items = [
					createCheckListItem('parent', { id: 'item1', sortIndex: 0 }),
					createCheckListItem('parent', { id: 'item2', sortIndex: 1 }),
					createCheckListItem('parent', { id: 'item3', sortIndex: 2 })
				];

				const manager = new CheckListManager({
					computed: { checkLists: () => [parent, ...items] }
				});

				manager.moveRight(items[2], mockUpdateFn);

				assert.strictEqual(updateCalls.length, 1);
				assert.strictEqual(updateCalls[0].id, 'item3');
				assert.strictEqual(updateCalls[0].parentId, 'item2');
			});
		});

		describe('moveLeft', () => {
			let mockUpdateFn;
			let updateCalls;

			beforeEach(() => {
				updateCalls = [];
				mockUpdateFn = (updates) => {
					updateCalls.push(...updates);
				};
			});

			it('should move item to parent level', () => {
				const grandparent = createCheckListItem(0, { id: 'grandparent' });
				const parent = createCheckListItem('grandparent', { id: 'parent', sortIndex: 0 });
				const child = createCheckListItem('parent', { id: 'child', sortIndex: 0 });

				const manager = new CheckListManager({
					computed: { checkLists: () => [grandparent, parent, child] }
				});

				manager.moveLeft(child, mockUpdateFn);

				assert.strictEqual(updateCalls.length, 1);
				assert.strictEqual(updateCalls[0].id, 'child');
				assert.strictEqual(updateCalls[0].parentId, 'grandparent');
				assert.strictEqual(updateCalls[0].sortIndex, 1);
			});

			it('should not move root items', () => {
				const rootItem = createCheckListItem(0, { id: 'root' });
				const manager = new CheckListManager({
					computed: { checkLists: () => [rootItem] }
				});

				manager.moveLeft(rootItem, mockUpdateFn);
				assert.strictEqual(updateCalls.length, 0);
			});

			it('should not move items from level 1', () => {
				const parent = createCheckListItem(0, { id: 'parent' });
				const child = createCheckListItem('parent', { id: 'child' });

				const manager = new CheckListManager({
					computed: { checkLists: () => [parent, child] }
				});

				manager.moveLeft(child, mockUpdateFn);
				assert.strictEqual(updateCalls.length, 0);
			});

			it('should adjust sibling indexes when moving', () => {
				const grandparent = createCheckListItem(0, { id: 'grandparent' });
				const parent = createCheckListItem('grandparent', { id: 'parent', sortIndex: 0 });
				const child1 = createCheckListItem('parent', { id: 'child1', sortIndex: 0 });
				const child2 = createCheckListItem('parent', { id: 'child2', sortIndex: 1 });

				const manager = new CheckListManager({
					computed: { checkLists: () => [grandparent, parent, child1, child2] }
				});

				manager.moveLeft(child1, mockUpdateFn);

				assert.strictEqual(updateCalls.length, 2);
				const movedItem = updateCalls.find(u => u.id === 'child1');
				const updatedSibling = updateCalls.find(u => u.id === 'child2');

				assert.strictEqual(movedItem.parentId, 'grandparent');
				assert.strictEqual(updatedSibling.sortIndex, 0);
			});

			it('should handle moving to same position of parent siblings', () => {
				const grandparent = createCheckListItem(0, { id: 'grandparent' });
				const parent1 = createCheckListItem('grandparent', { id: 'parent1', sortIndex: 0 });
				const child = createCheckListItem('parent1', { id: 'child', sortIndex: 0 });
				const parent2 = createCheckListItem('grandparent', { id: 'parent2', sortIndex: 1 });

				const manager = new CheckListManager({
					computed: { checkLists: () => [grandparent, parent1, parent2, child] }
				});

				manager.moveLeft(child, mockUpdateFn);

				const movedItem = updateCalls.find(u => u.id === 'child');

				assert.strictEqual(movedItem.sortIndex, 1);
			});
		});

		describe('findNearestItem', () => {
			let testManager;
			let testItems;

			beforeEach(() => {
				testItems = [
					createCheckListItem(0, { id: 'root', sortIndex: 0 }),
					createCheckListItem('root', { id: 'parent1', sortIndex: 0, groupMode: { selected: true } }),
					createCheckListItem('root', { id: 'parent2', sortIndex: 1, groupMode: { selected: false } }),
					createCheckListItem('parent1', { id: 'child1', sortIndex: 0, groupMode: { selected: true } }),
					createCheckListItem('parent1', { id: 'child2', sortIndex: 1, groupMode: { selected: true } }),
					createCheckListItem('parent2', { id: 'child3', sortIndex: 0, groupMode: { selected: false } })
				];

				testManager = new CheckListManager({
					computed: { checkLists: () => testItems }
				});
			});

			it('should return null if initialItem is null', () => {
				const result = testManager.findNearestItem(null, true);

				assert.strictEqual(result, null);
			});

			it('should find nearest selected item with higher sortIndex', () => {
				const initialItem = testItems.find(item => item.id === 'child1');
				const result = testManager.findNearestItem(initialItem, true);

				assert.notStrictEqual(result, null);
				assert.strictEqual(result?.id, 'child2');
			});

			it('should return null if initial item has no root parent', () => {
				const orphanItem = createCheckListItem('non-existent', { id: 'orphan' });
				testItems.push(orphanItem);

				const result = testManager.findNearestItem(orphanItem, true);

				assert.strictEqual(result, null);
			});

			it('should correctly find next item with higher sortIndex', () => {
				testItems.push(
					createCheckListItem('root', {
						id: 'parent3',
						sortIndex: 2,
						groupMode: { selected: false }
					})
				);

				const initialItem = testItems.find(item => item.id === 'parent2');
				const result = testManager.findNearestItem(initialItem, false);

				assert.strictEqual(result?.id, 'parent3');
			});
		});

		describe('getChildWithEmptyTitle', () => {
			it('should return null when no children exist', () => {
				const result = manager.getChildWithEmptyTitle('non-existent-id');

				assert.strictEqual(result, null);
			});

			it('should return null when no children have empty title', () => {
				const parent = demoCheckLists.find(item => item.parentId === 0);
				const result = manager.getChildWithEmptyTitle(parent.id);

				assert.strictEqual(result, null);
			});

			it('should work with mixed sort indexes', () => {
				const parentId = Text.getRandom();
				const items = [
					createCheckListItem(parentId, { id: 'child1', title: '', sortIndex: 100 }),
					createCheckListItem(parentId, { id: 'child2', title: 'Normal', sortIndex: 50 }),
					createCheckListItem(parentId, { id: 'child3', title: '', sortIndex: 200 }),
				];

				const testManager = new CheckListManager({
					computed: { checkLists: () => items }
				});

				const result = testManager.getChildWithEmptyTitle(parentId);

				assert.strictEqual(result?.id, 'child3');
			});

			it('should handle empty string vs whitespace correctly', () => {
				const parentId = Text.getRandom();
				const items = [
					createCheckListItem(parentId, { id: 'child1', title: '   ', sortIndex: 0 }),
					createCheckListItem(parentId, { id: 'child2', title: '', sortIndex: 1 }),
				];

				const testManager = new CheckListManager({
					computed: { checkLists: () => items }
				});

				const result = testManager.getChildWithEmptyTitle(parentId);

				assert.strictEqual(result?.id, 'child2');
			});
		});

		describe('isItemCollapsed', () => {
			it('should respect localCollapsedState when set', () => {
				const item = createCheckListItem(0, {
					localCollapsedState: true,
					collapsed: false,
					expanded: true,
				});

				assert.strictEqual(manager.isItemCollapsed(item, true, 0), true);
				assert.strictEqual(manager.isItemCollapsed(item, false, 0), true);
			});

			it('should return false in non-preview mode regardless of other conditions', () => {
				const item = createCheckListItem(0, {
					localCollapsedState: null,
					collapsed: true,
					expanded: false,
				});

				assert.strictEqual(manager.isItemCollapsed(item, false, 0), false);
				assert.strictEqual(manager.isItemCollapsed(item, false, 1), false);
			});

			it('should return true when collapsed=true and expanded=false in preview mode', () => {
				const item = createCheckListItem(0, {
					localCollapsedState: null,
					collapsed: true,
					expanded: false,
				});

				assert.strictEqual(manager.isItemCollapsed(item, true, 0), true);
			});

			it('should return false when expanded=true in preview mode', () => {
				const item = createCheckListItem(0, {
					localCollapsedState: null,
					collapsed: true,
					expanded: true,
				});

				assert.strictEqual(manager.isItemCollapsed(item, true, 0), false);
			});

			it('should return false for first item when no state is set', () => {
				const item = createCheckListItem(0, {
					localCollapsedState: null,
					collapsed: undefined,
					expanded: undefined,
				});

				assert.strictEqual(manager.isItemCollapsed(item, true, 0), false);
			});

			it('should return true for non-first items when no state is set', () => {
				const item = createCheckListItem(0, {
					localCollapsedState: null,
					collapsed: undefined,
					expanded: undefined,
				});

				assert.strictEqual(manager.isItemCollapsed(item, true, 1), true);
				assert.strictEqual(manager.isItemCollapsed(item, true, 2), true);
			});

			it('should prioritize expanded over collapsed when both are set', () => {
				const item = createCheckListItem(0, {
					localCollapsedState: null,
					collapsed: true,
					expanded: true,
				});

				assert.strictEqual(manager.isItemCollapsed(item, true, 0), false);
			});

			it('should handle undefined collapsed/expanded fields', () => {
				const item = createCheckListItem(0, {
					localCollapsedState: null,
					collapsed: undefined,
					expanded: undefined,
				});

				assert.strictEqual(manager.isItemCollapsed(item, true, 0), false);
				assert.strictEqual(manager.isItemCollapsed(item, true, 1), true);
			});

			it('should handle null collapsed/expanded fields', () => {
				const item = createCheckListItem(0, {
					localCollapsedState: null,
					collapsed: null,
					expanded: null,
				});

				assert.strictEqual(manager.isItemCollapsed(item, true, 0), false);
				assert.strictEqual(manager.isItemCollapsed(item, true, 1), true);
			});
		});

		describe('getRootParentByChildId', () => {
			it('should return the item itself for root items (parentId === 0)', () => {
				const rootItem = demoCheckLists.find(item => item.parentId === 0);
				const result = manager.getRootParentByChildId(rootItem.id);

				assert.strictEqual(result?.id, rootItem.id);
			});

			it('should return correct root parent for direct children', () => {
				const rootItem = demoCheckLists.find(item => item.parentId === 0);
				const childItem = demoCheckLists.find(item => item.parentId === rootItem.id);

				const result = manager.getRootParentByChildId(childItem.id);

				assert.strictEqual(result?.id, rootItem.id);
			});

			it('should return correct root parent for nested items (2+ levels deep)', () => {
				const rootItem = demoCheckLists.find(item => item.parentId === 0);
				const childItem = demoCheckLists.find(item => item.parentId === rootItem.id);
				const nestedItem = demoCheckLists.find(item => item.parentId === childItem.id);

				if (nestedItem)
				{
					const result = manager.getRootParentByChildId(nestedItem.id);

					assert.strictEqual(result?.id, rootItem.id);
				}
				else
				{
					assert.fail('Nested item not found in demo data');
				}
			});

			it('should return null for non-existent item', () => {
				const result = manager.getRootParentByChildId('non-existent-id');

				assert.strictEqual(result, null);
			});

			it('should handle circular references safely', () => {
				const item1 = createCheckListItem(0, { id: 'item1' });
				const item2 = createCheckListItem('item1', { id: 'item2' });

				item1.parentId = 'item2';

				const testManager = new CheckListManager({
					computed: {
						checkLists: () => [item1, item2],
					},
				});

				let result;
				assert.doesNotThrow(() => {
					result = testManager.getRootParentByChildId('item2');
				});

				assert(result === null || result?.id === 'item1');
			});

			it('should return null when parent chain is broken', () => {
				const orphanItem = createCheckListItem('missing-parent', { id: 'orphan' });
				const testManager = new CheckListManager({
					computed: {
						checkLists: () => [orphanItem],
					},
				});

				const result = testManager.getRootParentByChildId('orphan');

				assert.strictEqual(result, null);
			});

			it('should work with complex nested structures', () => {
				const root = createCheckListItem(0, { id: 'root' });
				const parent1 = createCheckListItem('root', { id: 'parent1' });
				const child1 = createCheckListItem('parent1', { id: 'child1' });
				const grandchild1 = createCheckListItem('child1', { id: 'grandchild1' });

				const parent2 = createCheckListItem('root', { id: 'parent2' });
				const child2 = createCheckListItem('parent2', { id: 'child2' });

				const testManager = new CheckListManager({
					computed: {
						checkLists: () => [root, parent1, child1, grandchild1, parent2, child2],
					},
				});

				assert.strictEqual(testManager.getRootParentByChildId('grandchild1')?.id, 'root');
				assert.strictEqual(testManager.getRootParentByChildId('child2')?.id, 'root');
				assert.strictEqual(testManager.getRootParentByChildId('parent1')?.id, 'root');
				assert.strictEqual(testManager.getRootParentByChildId('root')?.id, 'root');
			});

			it('should handle multiple root items correctly', () => {
				const root1 = createCheckListItem(0, { id: 'root1' });
				const root2 = createCheckListItem(0, { id: 'root2' });
				const childOfRoot1 = createCheckListItem('root1', { id: 'child1' });
				const childOfRoot2 = createCheckListItem('root2', { id: 'child2' });

				const testManager = new CheckListManager({
					computed: {
						checkLists: () => [root1, root2, childOfRoot1, childOfRoot2],
					},
				});

				assert.strictEqual(testManager.getRootParentByChildId('child1')?.id, 'root1');
				assert.strictEqual(testManager.getRootParentByChildId('child2')?.id, 'root2');
			});
		});

		describe('expandIdsWithChildren', () => {
			let manager;
			let demoCheckLists;

			beforeEach(() => {
				demoCheckLists = [
					createCheckListItem(0, { id: 'root1' }),
					createCheckListItem('root1', { id: 'child1' }),
					createCheckListItem('child1', { id: 'grandchild1' }),
					createCheckListItem(0, { id: 'root2' }),
					createCheckListItem('root2', { id: 'child2' }),
				];

				manager = new CheckListManager({
					computed: { checkLists: () => demoCheckLists },
				});
			});

			it('should include all nested children', () => {
				const input = new Set(['root1']);
				const result = manager.expandIdsWithChildren(input);

				assert.deepEqual([...result].sort(), ['root1', 'child1', 'grandchild1'].sort());
			});

			it('should work with multiple root ids', () => {
				const input = new Set(['root1', 'root2']);
				const result = manager.expandIdsWithChildren(input);

				assert.deepEqual(
					[...result].sort(),
					['root1', 'child1', 'grandchild1', 'root2', 'child2'].sort(),
				);
			});

			it('should handle empty input', () => {
				const result = manager.expandIdsWithChildren(new Set());
				assert.strictEqual(result.size, 0);
			});

			it('should handle circular references safely', () => {
				const item1 = createCheckListItem(0, { id: 'item1' });
				const item2 = createCheckListItem('item1', { id: 'item2' });
				item1.parentId = 'item2';

				const testManager = new CheckListManager({
					computed: { checkLists: () => [item1, item2] },
				});

				const result = testManager.expandIdsWithChildren(new Set(['item1']));
				assert.deepEqual([...result].sort(), ['item1', 'item2'].sort());
			});

			it('should not include unrelated items', () => {
				const input = new Set(['root1']);
				const result = manager.expandIdsWithChildren(input);

				assert(!result.has('root2'));
				assert(!result.has('child2'));
			});
		});

		describe('findItemIdsWithUser', () => {
			let manager;
			let testCheckLists;
			const testUserId = 123;

			beforeEach(() => {
				testCheckLists = [
					createCheckListItem(0, { id: 'root1' }),
					createCheckListItem(0, { id: 'root2' }),

					createCheckListItem('root1', {
						id: 'child1',
						accomplices: [{ id: testUserId, name: 'Test User', image: '', type: 'employee' }],
					}),
					createCheckListItem('root1', {
						id: 'child2',
						auditors: [{ id: testUserId, name: 'Test User', image: '', type: 'employee' }],
					}),

					createCheckListItem('root2', { id: 'child3' }),
					createCheckListItem('root2', {
						id: 'child4',
						accomplices: [{ id: 456, name: 'Other User', image: '', type: 'employee' }],
					}),

					createCheckListItem('child1', {
						id: 'grandchild1',
						auditors: [{ id: testUserId, name: 'Test User', image: '', type: 'employee' }],
					}),
					createCheckListItem('child1', {
						id: 'grandchild2',
						accomplices: [{ id: testUserId, name: 'Test User', image: '', type: 'employee' }],
					}),
					createCheckListItem('child3', { id: 'grandchild3' }),
				];

				manager = new CheckListManager({
					computed: { checkLists: () => testCheckLists },
				});
			});

			it('should find item ids where user is in accomplices or auditors', () => {
				const result = manager.findItemIdsWithUser('root1', testUserId);

				assert.strictEqual(result.size, 4);
				assert(result.has('child1'));
				assert(result.has('child2'));
				assert(result.has('grandchild1'));
				assert(result.has('grandchild2'));
			});

			it('should return empty set if no items contain the user', () => {
				const result = manager.findItemIdsWithUser('root2', testUserId);
				assert.strictEqual(result.size, 0);
			});

			it('should handle empty accomplices and auditors arrays', () => {
				const result = manager.findItemIdsWithUser('child3', testUserId);
				assert.strictEqual(result.size, 0);
			});

			it('should return empty set for non-existent parent item', () => {
				const result = manager.findItemIdsWithUser('non-existent', testUserId);
				assert.strictEqual(result.size, 0);
			});

			it('should handle items with user in both accomplices and auditors', () => {
				testCheckLists.push(
					createCheckListItem('root1', {
						id: 'child3',
						accomplices: [{ id: testUserId, name: 'Test User', image: '', type: 'employee' }],
						auditors: [{ id: testUserId, name: 'Test User', image: '', type: 'employee' }],
					}),
				);

				const result = manager.findItemIdsWithUser('root1', testUserId);
				assert(result.has('child3'));
			});

			it('should handle multiple users in accomplices/auditors', () => {
				const child1 = testCheckLists.find(item => item.id === 'child1');
				child1.accomplices.push(
					{ id: 456, name: 'Other User 1', image: '', type: 'employee' },
					{ id: 789, name: 'Other User 2', image: '', type: 'employee' },
				);

				const result = manager.findItemIdsWithUser('root1', testUserId);
				assert(result.has('child1'));
			});

			it('should work with deep nesting', () => {
				const greatGrandchild = createCheckListItem('grandchild1', {
					id: 'greatGrandchild',
					auditors: [{ id: testUserId, name: 'Test User', image: '', type: 'employee' }],
				});
				testCheckLists.push(greatGrandchild);

				const result = manager.findItemIdsWithUser('root1', testUserId);
				assert(result.has('greatGrandchild'));
				assert.strictEqual(result.size, 5);
			});
		});
	});

	describe('Group mode', () => {
		let testManager;
		let groupModeCheckLists = createGroupModeCheckLists();

		beforeEach(() => {
			testManager = new CheckListManager({
				computed: {
					checkLists: () => groupModeCheckLists,
				},
			});
		});

		describe('getAllGroupModeItems', () => {
			it('should return all items with active group mode', () => {
				const result = testManager.getAllGroupModeItems();

				assert.strictEqual(result.length, 6);
				assert(result.every(item => item.groupMode.active === true));
			});

			it('should return empty array when no items in group mode', () => {
				const emptyManager = new CheckListManager({
					computed: {
						checkLists: () => [createCheckListItem(0)],
					},
				});

				const result = emptyManager.getAllGroupModeItems();
				assert.strictEqual(result.length, 0);
			});
		});

		describe('getAllSelectedItems', () => {
			it('should return only selected items in group mode (non-root)', () => {
				const result = testManager.getAllSelectedItems();

				assert.strictEqual(result.length, 2);
				assert(result.every((item: CheckListModel) =>
					item.groupMode.selected === true &&
					item.parentId !== 0
				));
				assert(result.some((item: CheckListModel) => item.id === 'parent1'));
				assert(result.some((item: CheckListModel) => item.id === 'child1'));
			});

			it('should not include root items even if selected', () => {
				groupModeCheckLists.push(createCheckListItem(0, {
					id: 'selectedRoot',
					groupMode: { active: true, selected: true }
				}));

				const result = testManager.getAllSelectedItems();
				assert(!result.some((item: CheckListModel) => item.parentId === 0));
			});

			it('should return empty array when no items selected', () => {
				const noSelectionData = groupModeCheckLists.map((item: CheckListModel) => ({
					...item,
					groupMode: { ...item.groupMode, selected: false }
				}));

				const noSelectionManager = new CheckListManager({
					computed: {
						checkLists: () => noSelectionData,
					},
				});

				const result = noSelectionManager.getAllSelectedItems();
				assert.strictEqual(result.length, 0);
			});
		});

		describe('getAllSelectedItemsWithChildren', () => {
			it('should return selected items and all their children', () => {
				const result = testManager.getAllSelectedItemsWithChildren();

				assert.strictEqual(result.length, 4);

				assert(result.some((item: CheckListModel) => item.id === 'parent1'));
				assert(result.some((item: CheckListModel) => item.id === 'child1'));
				assert(result.some((item: CheckListModel) => item.id === 'child2'));
				assert(result.some((item: CheckListModel) => item.id === 'nestedChild'));

				assert(!result.some((item: CheckListModel) => item.id === 'parent2'));
			});

			it('should handle deep nesting correctly', () => {
				const [selectedId, deepNestedStructure] = createDeepNestedGroupModeStructure(5, 2);
				const deepManager = new CheckListManager({
					computed: { checkLists: () => deepNestedStructure }
				});

				const result = deepManager.getAllSelectedItemsWithChildren();

				assert.strictEqual(result.length, 3);
				assert(result.some((item: CheckListModel) => item.id === selectedId));
			});

			it('should return empty array when no items selected', () => {
				const noSelectionData = groupModeCheckLists.map((item: CheckListModel) => ({
					...item,
					groupMode: { ...item.groupMode, selected: false }
				}));

				const noSelectionManager = new CheckListManager({
					computed: {
						checkLists: () => noSelectionData,
					},
				});

				const result = noSelectionManager.getAllSelectedItemsWithChildren();

				assert.strictEqual(result.length, 0);
			});
		});
	});
});
