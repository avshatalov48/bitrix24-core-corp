import type { UserModel } from 'tasks.v2.model.users';

export type CheckListModelState = {
	collection: { [checkListId: string]: CheckListModel },
};

export type CheckListModel = {
	id: number | string,
	nodeId: number | string,
	title: string,
	creator: ?UserModel,
	toggledBy: ?UserModel,
	toggledDate: ?string,
	accomplices: ?UserModel[],
	auditors: ?UserModel[],
	attachments: Attachment[],
	isComplete: ?boolean,
	isImportant: ?boolean,
	parentId: ?number,
	parentNodeId: ?string,
	sortIndex: ?number,
	actions: {
		modify: boolean,
		remove: boolean,
		toggle: boolean
	},
	panelIsShown: boolean,
	myFilterActive: boolean,
	collapsed: boolean,
	expanded: boolean,
	localCompleteState: ?boolean,
	localCollapsedState: ?boolean,
	areCompletedCollapsed: boolean,
	hidden: boolean,
	groupMode: {
		active: boolean,
		selected: boolean,
	},
};

export type Attachment = {
	id: number | string,
	fileId: number | string,
};
