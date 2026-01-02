import {
	ChatsCopilotDataItem,
	CopilotRoleData,
	MessageCopilotDataItem
} from "../../../../model/dialogues/src/copilot/types";
import {messagesAutoDeleteConfigs} from "../../../../provider/pull/base/types/message";
import {RawChat, RawUser} from "../../../../provider/pull/base/types/common";

declare type imV2RecentCopilotResult = {
	hasMore: boolean,
	hasMorePages: boolean,
	items: Array<RecentItemData>,
	copilot: CopilotRecentItemData,
}

declare type imV2RecentChatsResult = {
	hasMore: boolean,
	hasMorePages: boolean,
	items: Array<RecentItemData>,
	copilot: CopilotRecentItemData,
	messagesAutoDeleteConfigs: Array<messagesAutoDeleteConfigs>,
}

declare type RecentItemData = {
	id: string,
	avatar: number,
	chat: RawChat,
	pinned: boolean,
	unread: boolean,
	chat_id: number,
	counter: number,
	date_last_activity: string,
	last_id: number,
	message: RecentItemMessageData,
	options: [],
	title: string,
	type: string,
	user: RawUser,
}

export type RecentItemMessageData = {
	attach: false,
	author_id: number,
	date: string,
	file: boolean | { id: number, type: string, name: string},
	id: number,
	status: string,
	text: string,
	uuid: string,
}

declare type CopilotRecentItemData = {
	messages: Array<MessageCopilotDataItem>,
	chats: Array<ChatsCopilotDataItem>,
	recommendedRoles?: Array<object>,
	engines: object,
	roles: Record<string, CopilotRoleData>,
}
