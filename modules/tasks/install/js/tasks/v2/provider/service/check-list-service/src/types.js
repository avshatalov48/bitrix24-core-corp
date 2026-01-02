import type { UserDto } from 'tasks.v2.provider.service.user-service';

export type CheckListDto = {
	id: number | string,
	nodeId: number | string,
	title: string,
	creator: ?UserDto,
	toggledBy: ?UserDto,
	toggledDate: ?string,
	accomplices: ?UserDto[],
	auditors: ?UserDto[],
	attachments: AttachmentDto[],
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

export type CheckListSliderData = {
	ID: number,
	NODE_ID: string,
	TITLE: string,
	CREATED_BY: number,
	TOGGLED_BY: number,
	TOGGLED_DATE: string,
	ACCOMPLICES: number[],
	AUDITORS: number[],
	ATTACHMENTS: string[],
	IS_COMPLETE: boolean,
	IS_IMPORTANT: boolean,
	PARENT_ID: string,
	PARENT_NODE_ID: string,
	SORT_INDEX: number,
	ACTIONS: {
		MODIFY: boolean,
		REMOVE: boolean,
		TOGGLE: boolean,
	},
};

export type AttachmentDto = {
	id: number | string,
	fileId: number | string,
};
