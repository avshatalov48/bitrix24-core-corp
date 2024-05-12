import { ReactionType, ReactionUser } from '../../../model/types/messages/reactions';
import { UsersModelState } from '../../../model/types/users';
import { DialoguesModelState } from '../../../model/types/dialogues';
import {RawFile, RawMessage, RawPin, RawUser} from "../../../service/src/types/sync-list-result";
import {RawReaction} from "../../../../model/types/messages/reactions";

export type AddReactionParams = {
	actualReactions: {
		reaction: RawReaction,
		usersShort: ReactionUser[]
	},
	reaction: ReactionType,
	userId: number,
	dialogId: string
};

export type DeleteReactionParams = {
	actualReactions: {
		reaction: RawReaction,
		usersShort: ReactionUser[]
	},
	reaction: ReactionType,
	userId: number
};

type MessagePullHandlerMessageDeleteCompleteParams = {
	chatId: number,
	counter: number,
	dialogId: string | number,
	fromUserId: number,
	id: number,
	lastMessageViews: {
		messageId: number,
		firstViewers: Array<number>,
		countOfViewers: number,
	},
	muted: false,
	newLastMessage: {
		id: number,
		uuid: string,
		author_id: number,
		chat_id: number,
		date: string,
		isSystem: boolean,
		text: string,
		unread: boolean,
		viewedByOthers: boolean,
		viewed: boolean,
		params: object,
		replaces: Array<any>,
		files?: Array<any>,
	},
	params: object,
	senderId: number,
	text: string,
	toUserId: number,
	type: string,
	unread: false,
};

type MessagePullHandlerUpdateDialogParams = {
	dialogId: string | number,
	chatId?: number,
	message: {
		id: number,
		senderId: number,
	},
	counter: number,
	users?: Record<number, UsersModelState>,
	chat: Record<number, Partial<DialoguesModelState>>,
	userInChat: Record<number, Array<number>>,
}

declare type MessagePullHandlerMessageParamsUpdateParams = {
	id: number, // message id
	chatId: number,
	type: 'private' | 'chat',
	senderId?: number, // only open chat
	fromUserId?: number, // only private chat
	toUserId?: number, // only private chat
	params: Object,
}

declare type MessagePullHandlerPinAddParams = {
	additionalMessages: Array<RawMessage>,
	files: Array<RawFile>,
	pin: RawPin,
	users: Array<RawUser>
}

declare type MessagePullHandlerPinDeleteParams = {
	chatId: number,
	linkId: number,
	messageId: number
}

declare type MessagePullHandlerAdditionalEntities = {
	additionalMessages: Array<RawMessage>,
	files: Array<RawFile>,
	messages: Array<RawMessage>,
	users: Array<RawUser>,
	reactions: any,
}