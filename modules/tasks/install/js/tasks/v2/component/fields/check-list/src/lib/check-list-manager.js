import type { CheckListModel } from 'tasks.v2.model.check-list';
import type { UserModel } from 'tasks.v2.model.users';

type Params = {
	computed?: {
		checkLists: () => CheckListModel[],
	},
};

export class CheckListManager
{
	#params: Params;

	constructor(params: Params)
	{
		this.#params = params;
	}

	getItem(itemId: number | string): ?CheckListModel
	{
		return this.#getCheckLists().find((item: CheckListModel) => item.id === itemId);
	}

	getItemsOnLevel(parentId: number | string): CheckListModel[]
	{
		return this.#getCheckLists()
			.filter((item: CheckListModel) => item.parentId === parentId)
			.sort((a: CheckListModel, b: CheckListModel) => a.sortIndex - b.sortIndex);
	}

	getItemLevel(checkListItem: CheckListModel): number
	{
		let level = 0;
		let current = checkListItem;

		const visitedIds = new Set();

		const findParent = (parentId) => this.#getCheckLists().find((item: CheckListModel) => item.id === parentId);

		while (current.parentId !== 0)
		{
			if (visitedIds.has(current.id))
			{
				break;
			}

			visitedIds.add(current.id);

			current = findParent(current.parentId);
			if (!current)
			{
				break;
			}
			level++;
		}

		return level;
	}

	isItemDescendant(potentialAncestor: ?CheckListModel, item?: CheckListModel): boolean
	{
		if (item?.parentId === potentialAncestor?.id)
		{
			return true;
		}

		if (item?.parentId === 0)
		{
			return false;
		}

		const parent = this.#getCheckLists().find((i: CheckListModel) => i.id === item?.parentId);
		if (!parent)
		{
			return false;
		}

		return this.isItemDescendant(potentialAncestor, parent);
	}

	showItems(
		itemIds: (number | string)[],
		updateFn: (updates: CheckListModel[]) => void,
	): void
	{
		const updates: CheckListModel[] = [];

		itemIds.forEach((itemId: number | string) => this.#setItemsVisibility(itemId, false, updates));

		if (updates.length > 0)
		{
			updateFn(updates);
		}
	}

	hideItems(
		itemIds: (number | string)[],
		updateFn: (updates: CheckListModel[]) => void,
	): void
	{
		const updates: CheckListModel[] = [];

		itemIds.forEach((itemId: number | string) => this.#setItemsVisibility(itemId, true, updates));

		if (updates.length > 0)
		{
			updateFn(updates);
		}
	}

	syncParentCompletionState(
		itemId: number | string,
		updateFn: (id: string | number, fields: Partial<CheckListModel>) => void,
		parentItemId?: number | string,
	): void
	{
		const changedItem = this.#getCheckLists().find((item: CheckListModel) => item.id === itemId);

		if ((!changedItem || !changedItem.parentId) && !parentItemId)
		{
			return;
		}

		const parentId = parentItemId || changedItem.parentId;

		const parentItem = this.#getCheckLists().find((item: CheckListModel) => item.id === parentId);
		if (!parentItem)
		{
			return;
		}

		const childrenItems = this.#getCheckLists().filter((item: CheckListModel) => item.parentId === parentItem.id);
		const isEmptyParent = (childrenItems.length === 0);

		const allChildrenCompleted = childrenItems.every((child: CheckListModel) => {
			return child.localCompleteState ?? child.isComplete;
		});
		const someChildrenIncomplete = !allChildrenCompleted;

		const parentCompleted = parentItem.localCompleteState ?? parentItem.isComplete;
		const shouldUpdateParent = (
			isEmptyParent
			|| (allChildrenCompleted && !parentCompleted)
			|| (someChildrenIncomplete && parentCompleted)
		);
		if (!shouldUpdateParent)
		{
			return;
		}

		updateFn(parentItem.id, {
			isComplete: allChildrenCompleted && !isEmptyParent,
		});

		if (parentItem.parentId)
		{
			this.syncParentCompletionState(parentItem.id, updateFn);
		}
	}

	getAllGroupModeItems(): CheckListModel[]
	{
		return this.#getCheckLists().filter((item: CheckListModel) => item.groupMode?.active === true);
	}

	getAllSelectedItems(): CheckListModel[]
	{
		return this.#getCheckLists().filter((item: CheckListModel) => {
			return (item.parentId !== 0 && item.groupMode?.selected === true);
		});
	}

	getAllSelectedItemsWithChildren(): CheckListModel[]
	{
		const result = new Map();

		const selectedItems = this.getAllSelectedItems();
		const allItems = this.#getCheckLists();

		selectedItems.forEach((item: CheckListModel) => result.set(item.id, item));

		const getChildren = (parentIds: (string | number)[]): void => {
			const children = allItems.filter((item: CheckListModel) => {
				return parentIds.includes(item.parentId) && !result.has(item.id);
			});

			children.forEach((child: CheckListModel) => result.set(child.id, child));

			if (children.length > 0)
			{
				getChildren(children.map((child: CheckListModel) => child.id));
			}
		};

		getChildren(selectedItems.map((item: CheckListModel) => item.id));

		return [...result.values()];
	}

	getAllChildren(itemId: number | string): CheckListModel[]
	{
		const visited = new Set();

		const result = [];

		const collectChildren = (currentId: number | string) => {
			if (visited.has(currentId))
			{
				return;
			}

			visited.add(currentId);

			const children = this.#getCheckLists()
				.filter((item: CheckListModel) => item.parentId === currentId)
				.sort((a: CheckListModel, b: CheckListModel) => a.sortIndex - b.sortIndex);

			children.forEach((child: CheckListModel) => {
				if (!visited.has(child.id))
				{
					result.push(child);
					collectChildren(child.id);
				}
			});
		};

		collectChildren(itemId);

		return result;
	}

	getAllCompletedChildren(itemId: number | string): CheckListModel[]
	{
		return this.getAllChildren(itemId).filter((item: CheckListModel) => {
			return (item.localCompleteState ?? item.isComplete) === true;
		});
	}

	getChildren(itemId: number | string): CheckListModel[]
	{
		return this.#getCheckLists().filter((item: CheckListModel) => {
			return item.parentId === itemId;
		});
	}

	getSiblings(itemId: number | string, parentId: number | string): CheckListModel[]
	{
		return this.#getCheckLists()
			.filter((sibling: CheckListModel) => sibling.parentId === parentId && sibling.id !== itemId)
			.sort((a: CheckListModel, b: CheckListModel) => a.sortIndex - b.sortIndex);
	}

	resortItemsOnLevel(
		parentId: number | string,
		updateFn: (updates: CheckListModel[]) => void,
	): void
	{
		const allItems = this.#getCheckLists()
			.filter((item: CheckListModel) => item.parentId === parentId);

		const sortedItems = [...allItems].sort((a: CheckListModel, b: CheckListModel) => a.sortIndex - b.sortIndex);

		const updates = sortedItems
			.map((item: CheckListModel, newIndex: number) => ({
				...item,
				sortIndex: newIndex,
			}));

		if (updates.length > 0)
		{
			updateFn(updates);
		}
	}

	resortItemsAfterIndex(
		parentId: number | string,
		sortIndex: number,
		updateFn: (updates: CheckListModel[]) => void,
	): void
	{
		const allItems = this.#getCheckLists()
			.filter((item: CheckListModel) => item.parentId === parentId);

		const itemsToResort = allItems
			.filter((item: CheckListModel) => item.sortIndex >= sortIndex)
			.sort((a: CheckListModel, b: CheckListModel) => a.sortIndex - b.sortIndex);

		const updates = itemsToResort
			.map((item: CheckListModel) => ({
				...item,
				sortIndex: item.sortIndex + 1,
			}));

		if (updates.length > 0)
		{
			updateFn(updates);
		}
	}

	moveRight(item: CheckListModel, updateFn: (updates: CheckListModel[]) => void): void
	{
		if (item.parentId === 0 || this.getItemLevel(item) > 5)
		{
			return;
		}

		const itemsOnLevel = this.getItemsOnLevel(item.parentId);

		const currentIndex = itemsOnLevel.findIndex((sibling: CheckListModel) => sibling.id === item.id);
		if (currentIndex <= 0)
		{
			return;
		}

		let newParent = null;
		for (let i = currentIndex - 1; i >= 0; i--)
		{
			const candidate = itemsOnLevel[i];
			if (!this.isItemDescendant(candidate, item))
			{
				newParent = candidate;
				break;
			}
		}

		if (!newParent)
		{
			return;
		}

		const newParentChildren = this.#getCheckLists()
			.filter((child: CheckListModel) => child.parentId === newParent.id)
			.sort((a: CheckListModel, b: CheckListModel) => a.sortIndex - b.sortIndex);

		const updates = itemsOnLevel
			.filter((sibling: CheckListModel, index: number) => index > currentIndex)
			.map((sibling: CheckListModel) => ({
				...sibling,
				sortIndex: sibling.sortIndex - 1,
			}));

		updates.push({
			...item,
			parentId: newParent.id,
			parentNodeId: newParent.nodeId,
			sortIndex: newParentChildren.length > 0
				? newParentChildren[newParentChildren.length - 1].sortIndex + 1
				: 0,
		});

		updateFn(updates);
	}

	moveLeft(item: CheckListModel, updateFn: (updates: CheckListModel[]) => void): void
	{
		if (item.parentId === 0 || this.getItemLevel(item) <= 1)
		{
			return;
		}

		const currentParent = this.#getCheckLists().find((parent: CheckListModel) => parent.id === item.parentId);
		if (!currentParent)
		{
			return;
		}

		const itemsOnLevel = this.getItemsOnLevel(currentParent.parentId);

		const parentInNewListIndex = itemsOnLevel
			.findIndex((sibling: CheckListModel) => sibling.id === currentParent.id);

		const currentSiblingsUpdates = this.#getCheckLists()
			.filter((sibling: CheckListModel) => (
				sibling.parentId === item.parentId
				&& sibling.sortIndex > item.sortIndex
			))
			.map((sibling: CheckListModel) => ({
				...sibling,
				sortIndex: sibling.sortIndex - 1,
			}));

		let newSortIndex = 0;
		if (
			parentInNewListIndex === -1
			|| parentInNewListIndex === itemsOnLevel.length - 1
		)
		{
			newSortIndex = itemsOnLevel.length > 0 ? itemsOnLevel[itemsOnLevel.length - 1].sortIndex + 1 : 0;
		}
		else
		{
			newSortIndex = itemsOnLevel[parentInNewListIndex].sortIndex + 1;

			const shiftUpdates = itemsOnLevel
				.filter((sibling: CheckListModel) => sibling.sortIndex >= newSortIndex)
				.map((sibling: CheckListModel) => ({
					...sibling,
					sortIndex: sibling.sortIndex + 1,
				}));

			currentSiblingsUpdates.push(...shiftUpdates);
		}

		const movedItemUpdate = {
			...item,
			parentId: currentParent.parentId,
			parentNodeId: currentParent.parentNodeId || null,
			sortIndex: newSortIndex,
		};

		updateFn([...currentSiblingsUpdates, movedItemUpdate]);
	}

	findNearestItem(
		initialItem: CheckListModel,
		selected: boolean,
		excludeChildrenOf: CheckListModel[] = [],
	): ?CheckListModel
	{
		if (!initialItem)
		{
			return null;
		}

		const rootParent = this.#getRootParent(initialItem);
		if (!rootParent)
		{
			return null;
		}

		const currentSortIndex = initialItem.sortIndex;

		const excludedParentIds = new Set(excludeChildrenOf.map((item: CheckListModel) => item.id));

		const eligibleItems = this.#getCheckLists()
			.sort((a: CheckListModel, b: CheckListModel) => a.sortIndex - b.sortIndex)
			.filter((item: CheckListModel) => {
				const isChildOfExcluded = excludedParentIds.has(item.parentId);

				return (
					item.id !== initialItem.id
					&& item.parentId !== 0
					&& item.groupMode?.selected === selected
					&& this.#getRootParent(item)?.id === rootParent.id
					&& !isChildOfExcluded
				);
			});

		if (eligibleItems.length === 0)
		{
			return null;
		}

		return eligibleItems.reduce((nearest: CheckListModel, item: CheckListModel) => {
			return (
				item.sortIndex > currentSortIndex
				&& (
					item.sortIndex < nearest.sortIndex
					|| nearest.sortIndex <= currentSortIndex
				)
			) ? item : nearest;
		});
	}

	getFirstChild(itemId: number | string): ?CheckListModel
	{
		const children = this.#getCheckLists()
			.filter((item: CheckListModel) => item.parentId === itemId)
			.sort((a: CheckListModel, b: CheckListModel) => a.sortIndex - b.sortIndex);

		return children[0] || null;
	}

	hasEmptyItemWithFiles(hasItemFiles: (item: CheckListModel) => boolean): boolean
	{
		return this.#getCheckLists().some((item: CheckListModel) => {
			return item.title === '' && hasItemFiles(item);
		});
	}

	hasItemWithFiles(hasItemFiles: (item: CheckListModel) => boolean): boolean
	{
		return this.#getCheckLists().some((item: CheckListModel) => {
			return hasItemFiles(item);
		});
	}

	hasEmptyParentItem(): boolean
	{
		return this.#getCheckLists().some((item: CheckListModel) => {
			return item.parentId === 0 && item.title === '';
		});
	}

	getFirstEmptyItem(): ?CheckListModel
	{
		const items = this.#getCheckLists()
			.filter((item: CheckListModel) => item.title === '')
			.sort((a: CheckListModel, b: CheckListModel) => a.sortIndex - b.sortIndex);

		return items[0] || null;
	}

	getChildWithEmptyTitle(itemId: number | string): ?CheckListModel
	{
		const children = this.#getCheckLists()
			.filter((item: CheckListModel) => item.parentId === itemId)
			.sort((a: CheckListModel, b: CheckListModel) => b.sortIndex - a.sortIndex)
			.find((item: CheckListModel) => item.title === '');

		return children || null;
	}

	isItemCollapsed(item: CheckListModel, isPreview: boolean, positionIndex: number): boolean
	{
		if (item.localCollapsedState !== null)
		{
			return item.localCollapsedState;
		}

		if (!isPreview)
		{
			return false;
		}

		if (item.collapsed && !item.expanded)
		{
			return true;
		}

		if (item.expanded)
		{
			return false;
		}

		return positionIndex !== 0;
	}

	getRootParentByChildId(itemId: number | string): ?CheckListModel
	{
		const childItem = this.getItem(itemId);
		if (!childItem)
		{
			return null;
		}

		if (childItem.parentId === 0)
		{
			return childItem;
		}

		let currentItem = childItem;

		const visitedIds = new Set();

		while (currentItem && currentItem.parentId !== 0)
		{
			if (visitedIds.has(currentItem.id))
			{
				break;
			}

			visitedIds.add(currentItem.id);

			const parent = this.getItem(currentItem.parentId);
			if (!parent)
			{
				break;
			}

			currentItem = parent;
		}

		return currentItem?.parentId === 0 ? currentItem : null;
	}

	expandIdsWithChildren(itemIds: Set<string | number>): Set<string | number>
	{
		const fullSet = new Set(itemIds);

		if (itemIds.size === 0 || this.#getCheckLists().length === 0)
		{
			return fullSet;
		}

		const checkListMap = new Map(this.#getCheckLists().map((item: CheckListModel) => [item.id, item]));

		const processedIds = new Set();

		itemIds.forEach((id: string | number) => {
			if (!processedIds.has(id))
			{
				this.#findNestedChildren(id, checkListMap, fullSet, processedIds);
			}
		});

		return fullSet;
	}

	findItemIdsWithUser(rootId: number | string, userId: number): Set<string | number>
	{
		const allItems = this.getAllChildren(rootId);
		const rootItem = this.getItem(rootId);

		if (rootItem)
		{
			allItems.unshift(rootItem);
		}

		const result = new Set();
		allItems.forEach((item: CheckListModel) => {
			const hasUser = (
				item.accomplices?.some((user: UserModel) => user.id === userId)
				|| item.auditors?.some((user: UserModel) => user.id === userId)
			);

			if (hasUser && item.parentId !== 0)
			{
				result.add(item.id);
			}
		});

		return result;
	}

	#setItemsVisibility(
		itemId: number | string,
		hidden: boolean,
		updates: CheckListModel[],
	): void
	{
		const item = this.getItem(itemId);
		if (!item || item.hidden === hidden)
		{
			return;
		}

		const updatedItem = { ...item, hidden };
		updates.push(updatedItem);

		const children = this.getChildren(itemId);
		children.forEach((child: CheckListModel) => {
			this.#setItemsVisibility(child.id, hidden, updates);
		});
	}

	#findNestedChildren(
		parentId: string | number,
		checkListMap: Map<string | number, CheckListModel>,
		resultSet: Set<string | number>,
		processedIds: Set<string | number>,
	): void
	{
		if (processedIds.has(parentId))
		{
			return;
		}

		processedIds.add(parentId);

		checkListMap.forEach((item: CheckListModel) => {
			if (item.parentId === parentId && !resultSet.has(item.id))
			{
				resultSet.add(item.id);

				this.#findNestedChildren(item.id, checkListMap, resultSet, processedIds);
			}
		});
	}

	#getRootParent(item: CheckListModel): ?CheckListModel
	{
		if (!item || item.parentId === 0)
		{
			return item || null;
		}

		const parentItem = this.#getCheckLists().find((parent: CheckListModel) => parent.id === item.parentId);
		if (!parentItem)
		{
			return null;
		}

		return this.#getRootParent(parentItem);
	}

	#getCheckLists(): CheckListModel[]
	{
		return this.#params?.computed?.checkLists() ?? [];
	}
}
