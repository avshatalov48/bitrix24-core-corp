import {DialogId} from "../../../../types/common";
import {messagesAutoDeleteConfigs} from "../../../../provider/pull/base/types/message";
import {RawChat, RawFile, RawMessage, RawUser} from "../../../../provider/pull/base/types/common";
import {DialogPermissions, DialogType} from "../../../../model/dialogues/src/types";

export type imV2CollabTailResult = {
	additionalMessages: Array<RawMessage>,
	chats: Array<CollabTailResultChatsData>,
	files: Array<RawFile>,
	hasNextPage: boolean,
	messages: Array<CollabTailResultMessageData>,
	recentItems: Array<CollabRecentItemData>,
	users: Array<RawUser>,
	messagesAutoDeleteConfigs: Array<messagesAutoDeleteConfigs>
}

export type CollabRecentItemData = {
	dialogId: DialogId,
	chatId: number,
	counter: number,
	messageId: number,
	lastReadMessageId: number,
	pinned: boolean,
	unread: boolean,
	dateUpdate: string,
	dateLastActivity: string,
	options: [],
	invited: [],
}

export type CollabTailResultChatsData = {
	avatar: string,
	backgroundId: null | string,
	color: string,
	containsCollaber: boolean,
	description: string,
	dialogId: string,
	diskFolderId: number,
	entityData1: string,
	entityData2: string,
	entityData3: string,
	entityId: string,
	entityLink: object,
	entityType: string,
	extranet: boolean,
	id: number,
	isNew: boolean,
	messageType: string,
	muteList: any[],
	name: string,
	owner: number,
	parentChatId: number
	parentMessageId: number,
	permissions: DialogPermissions,
	role: string,
	textFieldEnabled: boolean,
	type: DialogType,
}

export type CollabTailResultMessageData = {
	author_id: number,
	chat_id: number,
	date: string,
	forward: null | any,
	id: number,
	isSystem: boolean,
	params: object,
	text: string,
	unread: boolean,
	uuid: null | string,
	viewed: boolean,
	viewedByOthers: boolean,
}
