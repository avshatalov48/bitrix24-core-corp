import {RawChat, RawFile, RawMessage, RawUser} from "../../../../provider/service/src/types/sync-list-result";
import {DialogId} from "../../../../types/common";

declare type imV2RecentChannelTailResult = {
	additionalMessages: Array<RawMessage>,
	chats: Array<RawChat>,
	files: Array<RawFile>,
	hasNextPage: boolean,
	messages: Array<RawMessage>,
	recentItems: Array<ChannelRecentItemData>,
	users: Array<RawUser>,
}

export type ChannelRecentItemData = {
	chatId: number,
	dialogId: DialogId,
	invited: [],
	messageId: number,
	options: [],
	pinned: boolean,
	unread: boolean,
}