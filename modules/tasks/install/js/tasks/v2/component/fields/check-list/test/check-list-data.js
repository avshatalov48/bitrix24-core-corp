import { Text } from 'main.core';

import type { CheckListModel } from 'tasks.v2.model.check-list';

export const createCheckListItem = (
	parentId: number | string = 0,
	overrides: Partial<CheckListModel> = {}
): CheckListModel => {
	const id = overrides.id ?? Text.getRandom();

	return {
		id,
		nodeId: id,
		title: `Item ${id}`,
		creator: null,
		toggledBy: null,
		toggledDate: null,
		accomplices: [],
		auditors: [],
		attachments: [],
		isComplete: false,
		isImportant: false,
		parentId,
		parentNodeId: parentId.toString(),
		sortIndex: 0,
		actions: {
			modify: true,
			remove: true,
			toggle: true,
		},
		panelIsShown: false,
		collapsed: false,
		hidden: false,
		groupMode: {
			active: false,
			selected: false,
		},
		...overrides,
	};
};

export const createDemoCheckLists = (): CheckListModel[] => {
	const rootItems = [
		createCheckListItem(0, { title: 'Root Item 1' }),
		createCheckListItem(0, { title: 'Root Item 2' }),
		createCheckListItem(0, { title: 'Root Item 3' }),
	];

	const childItems = rootItems.flatMap((rootItem: CheckListModel) => [
		createCheckListItem(rootItem.id, { title: `Child of ${rootItem.title}` }),
		createCheckListItem(rootItem.id, { title: `Second child of ${rootItem.title}` }),
	]);

	const nestedItems = childItems.map((childItem: CheckListModel) =>
		createCheckListItem(childItem.id, { title: `Nested under ${childItem.title}` })
	);

	return [...rootItems, ...childItems, ...nestedItems];
};

export const createGroupModeCheckLists = (): CheckListModel[] => {
	const rootId = 'root1';
	const parentId1 = 'parent1';
	const parentId2 = 'parent2';
	const childId1 = 'child1';
	const childId2 = 'child2';
	const nestedChildId = 'nestedChild';

	const rootItemWithActiveGroupMode = createCheckListItem(0, {
		id: rootId,
		groupMode: { active: true, selected: false }
	});
	const parentItems = [
		createCheckListItem(rootId, {
			id: parentId1,
			groupMode: { active: true, selected: true }
		}),
		createCheckListItem(rootId, {
			id: parentId2,
			groupMode: { active: true, selected: false }
		}),
	];
	const children = [
		createCheckListItem(parentId1, {
			id: childId1,
			groupMode: { active: true, selected: true }
		}),
		createCheckListItem(parentId1, {
			id: childId2,
			groupMode: { active: true, selected: false }
		}),
	];
	const nestedChildItem = createCheckListItem(childId1, {
		id: nestedChildId,
		groupMode: { active: true, selected: false }
	});
	const itemWithoutGroupMode = createCheckListItem(0, {
		id: 'regularItem',
		groupMode: { active: false, selected: false }
	});

	return [
		rootItemWithActiveGroupMode,
		...parentItems,
		...children,
		nestedChildItem,
		itemWithoutGroupMode,
	];
};

export const createDeepNestedGroupModeStructure = (depth: number, selectedLevel: number): CheckListModel[] => {
	const items: CheckListModel[] = [];

	let parentId = 0;
	let selectedId = null;

	for (let i = 0; i < depth; i++)
	{
		const item = createCheckListItem(
			parentId,
			{
				title: `Level ${i} Item`,
				groupMode: {
					active: true,
					selected: i === selectedLevel,
				},
			}
		);

		items.push(item);
		parentId = item.id;

		if (i === selectedLevel)
		{
			selectedId = item.id;
		}
	}

	return [selectedId, items];
};
