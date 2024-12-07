import {RawChat, RawFile, RawMessage, RawPin, RawReaction, RawShortUser, RawUser} from "./sync-list-result";
import {CommentInfoModelState} from "../../../../model/types/comment";

declare type ImV2ChatPinTailResult = {
	additionalMessages: Array<RawMessage>,
	files: Array<RawFile>,
	reactions: Array<RawReaction>,

	pins: Array<RawPin>,
	reminders: Array<any>,
	users: Array<RawUser>,
}

declare type ImV2ChatLoadResult = {
	additionalMessages: Array<RawMessage>,
	chat: RawChat,
	files: Array<RawFile>,
	hasNextPage: boolean,
	hasPrevPage: boolean,
	messages: Array<RawMessage>,
	pins: Array<RawPin>,
	reactions: Array<RawReaction>,
	reminders: [],
	users: Array<RawUser>,
	usersShort: Array<RawShortUser>,
	commentInfo?: Array<CommentInfoModelState>,
}