import {ReactionType, ReactionUser, RawReaction} from '../../../../model/messages/src/reactions/types';
import {UserRole, UsersModelState} from '../../../../model/users/src/types';
import {DialogType, DialoguesModelState} from '../../../../model/dialogues/src/types';
import {MessageParams} from '../../../../model/messages/src/types/messages';
import {CopilotRecentItemData} from "../../../../controller/recent/copilot/types/recent";
import {RecentConfigSections} from "./recent";
import {RawFile, RawMessage, RawPin, RawUser} from "./common";
import {DialogId} from "../../../../types/common";

export type MessagesAutoDeleteDelayParams = {
	delay: number,
	chatId: number,
	dialogId: string,
};

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
	muted: boolean,
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
		files?: Array<any>,
	},
	params: object,
	senderId: number,
	text: string,
	toUserId: number,
	type: string,
	unread: boolean,
};

type MessagePullHandlerMessageDeleteV2Params = {
	chatId: number,
	counter: number,
	dialogId: string | number,
	fromUserId: number,
	counterType: string, // see im/messenger/const/counter
	lastMessageViews: {
		messageId: number,
		firstViewers: Array<number>,
		countOfViewers: number,
	},
	messages: Array<DeleteV2MessageObject>,
	muted: boolean,
	newLastMessage: NewLastMessageDataMessageDeleteV2Params,
	toUserId: number,
	unread: boolean,
	type: 'private' | 'chat',
};

export type NewLastMessageDataMessageDeleteV2Params = {
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
	params: MessageParams,
	file?: object,
}

export type ReadMessageParams = {
	chatId: number,
	counter: number,
	dialogId: string,
	lastId: number,
	lines: boolean,
	muted: boolean,
	unread: boolean,
	viewedMessages: number[],
	type: string,
	parentChatId: number,
	counterType: string
};

export type ReadMessageOpponentParams = {
	chatId: number,
	chatMessageStatus: string,
	date: string,
	dialogId: string,
	lastId: number,
	userId: number,
	userName: string,
	viewedMessages: number[]
};

type DeleteV2MessageObject = {
	completelyDeleted: boolean,
	id: number,
	params: MessageParams,
	senderId: number,
	text: string,
}

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
	copilot: CopilotRecentItemData,
	messagesAutoDeleteConfigs: Array<messagesAutoDeleteConfigs>
}

type messagesAutoDeleteConfigs = {
	chatId: number,
	delay: number,
}

declare type MessagePullHandlerUpdateParams = {
	id: number, // message id
	chatId: number,
	dialogId: DialogId,
	params: MessageParams,
	type: string,
	senderId?: number, // only open chat
	fromUserId?: number, // only private chat
	toUserId?: number, // only private chat
	text: string,
	textLegacy: string,
}

declare type MessagePullHandlerMessageParamsUpdateParams = {
	id: number, // message id
	chatId: number,
	params: MessageParams,
	type: string,
	senderId?: number, // only open chat
	fromUserId?: number, // only private chat
	toUserId?: number, // only private chat
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

declare type MessageAddParams = {
	chat?: {[chatId: string]: MessageAddParamsRawChat} | [],
	chatId: number,
	copilot: CopilotRecentItemData | null,
	counter: number,
	counterType: string, // see im/messenger/const/counter,
	dateLastActivity: string,
	dialogId: string,
	files: {[fileId: string]: RawFile} | [],
	lines: null,
	message: RawMessage,
	messagesAutoDeleteConfigs: { delay: number, chatId: number }[],
	multidialog: [],
	notify: boolean,
	recentConfig: {
		chatId: number,
		sections: RecentConfigSections,
	},
	userBlockChat: {[chatId: string]: {[userId: string]: boolean}} | [],
	userInChat: {[chatId: string]: Array<number>} | [],
	users: {[userId: string]: RawUser} | null,
};


export type MessageAddParamsRawChat = {
	ai_provider: null | string,
	avatar: string,
	backgroundId: string | null,
	call: number,
	call_number: string,
	color: string,
	counter: number,
	contains_collaber: boolean,
	date_create: string,
	description: string,
	dialogId: string,
	entity_data_1: string,
	entity_data_2: string,
	entity_data_3: string,
	entityId: string,
	entity_type: string,
	entity_link: { type: string, url: string },
	extranet: boolean,
	id: number,
	isNew: boolean,
	manager_list: number[],
	message_count: number,
	message_type: string,
	muteList: number[],
	name: string,
	owner: number,
	parent_chat_id: number,
	parent_Message_id: number,
	permissions: {
		canPost: UserRole,
		manageUsersAdd: UserRole,
		manageUsersDelete: UserRole,
		manageUi: UserRole,
		manageSettings: UserRole,
		manageMessages: UserRole
	}
	public: string,
	role: string,
	type: DialogType
}
