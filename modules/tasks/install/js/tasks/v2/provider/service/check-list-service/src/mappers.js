import { UserTypes } from 'tasks.v2.model.users';
import { UserMappers } from 'tasks.v2.provider.service.user-service';
import type { CheckListModel } from 'tasks.v2.model.check-list';

import type { CheckListSliderData, CheckListDto } from './types';

export function prepareCheckLists(checklist: CheckListModel[]): CheckListModel[]
{
	const parentNodeIdMap = new Map();
	checklist.forEach((item: CheckListModel) => {
		parentNodeIdMap.set(item.id, item.nodeId);
	});

	return checklist.map((item: CheckListModel) => {
		const title = prepareTitle(item);
		const parentNodeId = item.parentId ? parentNodeIdMap.get(item.parentId) : 0;

		return { ...item, title, parentNodeId };
	});
}

export function mapDtoToModel(checkList: CheckListModel): CheckListDto
{
	return {
		id: checkList.id,
		nodeId: checkList.nodeId,
		title: checkList.title,
		creator: checkList.creator ? UserMappers.mapDtoToModel(checkList.creator) : null,
		toggledBy: checkList.toggledBy ? UserMappers.mapDtoToModel(checkList.toggledBy) : null,
		toggledDate: checkList.toggledDate,
		accomplices: checkList.accomplices?.map((it) => UserMappers.mapDtoToModel(it)),
		auditors: checkList.auditors?.map((it) => UserMappers.mapDtoToModel(it)),
		attachments: checkList.attachments,
		isComplete: checkList.isComplete,
		isImportant: checkList.isImportant,
		parentId: checkList.parentId,
		parentNodeId: checkList.parentNodeId,
		sortIndex: checkList.sortIndex,
		actions: checkList.actions,
		panelIsShown: checkList.panelIsShown,
		myFilterActive: checkList.myFilterActive,
		collapsed: checkList.collapsed,
		expanded: checkList.expanded,
		localCompleteState: checkList.localCompleteState,
		localCollapsedState: checkList.localCollapsedState,
		areCompletedCollapsed: checkList.areCompletedCollapsed,
		hidden: checkList.hidden,
		groupMode: checkList.groupMode,
	};
}

export function mapModelToSliderData(checkLists: CheckListModel[]): CheckListSliderData[]
{
	return Object.fromEntries(
		checkLists.map((item) => {
			const accomplices = item.accomplices?.map((accomplice) => ({
				ID: accomplice.id,
				TYPE: 'A',
				NAME: accomplice.name,
				IMAGE: accomplice.image,
				IS_COLLABER: accomplice.type === UserTypes.Collaber ? 1 : '',
			}));

			const auditors = item.auditors?.map((auditor) => ({
				ID: auditor.id,
				TYPE: 'U',
				NAME: auditor.name,
				IMAGE: auditor.image,
				IS_COLLABER: auditor.type === UserTypes.Collaber ? 1 : '',
			}));

			const attachments = Object.fromEntries(item.attachments?.map((key) => [key, key]));
			const members = [...accomplices, ...auditors].reduce((acc, curr) => {
				acc[curr.ID] = curr;

				return acc;
			}, {});

			const title = prepareTitle(item);

			const node = Object.fromEntries(
				Object.entries({
					NODE_ID: item.nodeId,
					TITLE: title,
					CREATED_BY: item.creator?.id,
					TOGGLED_BY: item.toggledBy?.id,
					TOGGLED_DATE: item.toggledDate,
					MEMBERS: members,
					NEW_FILE_IDS: attachments,
					ATTACHMENTS: attachments,
					IS_COMPLETE: item.isComplete,
					IS_IMPORTANT: item.isImportant,
					PARENT_ID: item.parentId,
					SORT_INDEX: item.sortIndex,
					ACTIONS: {
						MODIFY: item.actions.modify,
						REMOVE: item.actions.remove,
						TOGGLE: item.actions.toggle,
					},
				}).filter(([, value]) => value !== null && value !== undefined),
			);

			return [item.nodeId, node];
		}),
	);
}

export function getUserIdsFromChecklists(
	checkLists: CheckListModel[],
	userType: 'accomplices' | 'auditors',
): number[]
{
	return checkLists
		.flatMap((item: CheckListModel) => (item[userType] || []).map((user: User) => user.id))
		.filter((id, idx, arr) => arr.indexOf(id) === idx);
}

export function prepareTitle(item: CheckListModel): string
{
	const names = [
		...(item.accomplices ?? []).map((member) => member.name),
		...(item.auditors ?? []).map((member) => member.name),
	].join(' ');

	if (names)
	{
		return `${item.title} ${names}`;
	}

	return item.title;
}
