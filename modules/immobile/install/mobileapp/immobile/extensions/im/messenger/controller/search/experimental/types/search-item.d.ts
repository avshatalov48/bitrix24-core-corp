import { UsersModelState } from '../../../../model/users/src/types';
import { DialogOptionalParams, DialogType } from '../../../../model/dialogues/src/types';
import { CopilotRoleData } from '../../../../model/dialogues/src/copilot/types';

export type RecentProviderItem = {
	id: string,
	entityId: string,
	entityType: string,
	title: string,
	avatar: string,
	sort: number,
	customData: RecentProviderItemCustomData,
};

export type RecentProviderItemCustomData = {
	dateMessage: string | null,
	id: string,
	secondSort: number,
	byUser?: boolean,
	chat: RecentProviderChatItem,
	user?: RecentProviderUserItem,
	copilot?: CopilotRoleData,
}

export type RecentProviderUserItem = UsersModelState & {
	botData: null | object;
}
export type RecentProviderChatItem = {
	avatar: string,
	color: string,
	description: string,
	dialogId: string,
	diskFolderId: number,
	entityData1: string,
	entityData2: string,
	entityData3: string,
	entityId: string,
	entityLink: object,
	entityType: string,
	extranet: false,
	id: number,
	isNew: false,
	messageType: string,
	muteList: Array<number>,
	name: string,
	backgroundId: string,
	textFieldEnabled: boolean,
	owner: number,
	parentChatId: number,
	parentMessageId: number,
	permissions: string,
	role: string,
	type: string,
}

export type RecentProviderUserDataForDialogModel = {
	dialogId: string,
	avatar: string,
	color: string,
	name: string,
	type: DialogType.user,
	backgroundId: string,
	textFieldEnabled: boolean,
}
