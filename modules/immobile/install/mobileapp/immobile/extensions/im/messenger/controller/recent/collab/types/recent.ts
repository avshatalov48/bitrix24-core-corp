import {RawChat, RawFile, RawMessage, RawUser} from "../../../../provider/service/src/types/sync-list-result";
import {DialogId} from "../../../../types/common";

declare type imV2CollabTailResult = {
	additionalMessages: Array<RawMessage>,
	chats: Array<RawChat>,
	files: Array<RawFile>,
	hasNextPage: boolean,
	messages: Array<RawMessage>,
	recentItems: Array<CollabRecentItemData>,
	users: Array<RawUser>,
}

declare type CollabRecentItemData = {
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
