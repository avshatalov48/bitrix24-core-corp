import {MessagesAutoDeleteConfigs} from "../../../pull/base/types/message";
import {UserRole} from "../../../../model/users/src/types";
import {DialogType} from "../../../../model/dialogues/src/types";
import {
	ChatsCopilotDataItem,
	CopilotRoleData,
	MessageCopilotDataItem
} from "../../../../model/dialogues/src/copilot/types";

declare type SyncListResult = {
	chatSync: {
		addedChats: Record<string, number> | [],
		addedRecent: Record<string, number> | [],
		completeDeletedChats: Record<string, number> | [],
		deletedChats: Record<string, number> | [],
	}
	chats: Array<SyncRawChat>,
	copilot: null | CopilotSyncData,
	dialogIds: Record<number, string> | [],
	navigationData: {
		lastServerDate: string | null, // null only when the log is empty on server
		hasMore: boolean,
		lastId: number | null,
	}
	files: Array<SyncRawFile>,
	reactions: Array<SyncRawReaction>,
	messages: Array<SyncRawMessage>,
	additionalMessages: Array<SyncRawMessage>,
	messagesAutoDeleteConfigs: Array<MessagesAutoDeleteConfigs>, // use only in vuex
	messageSync: {
		addedMessages: Record<number, number> | [],
		completeDeletedMessages: Record<number, number> | [],
		updatedMessages: Record<number, number> | []
	},
	pinSync: {
		addedPins: Record<number, number> | [],
		deletedPins: Record<number, number> | [],
	}
	pins: Array<SyncRawPin>,

	recentItems: Array<SyncRawRecentItem>,
	users: Array<SyncRawUser>,
	usersShort: Array<SyncRawShortUser>,
}

export type SyncRequestResultReceivedEvent = {
	uuid: string,
	result: SyncListResult,
}

export type CopilotSyncData = {
	aiProvider: string
	chats: Array<ChatsCopilotDataItem> | null,
	messages: Array<MessageCopilotDataItem> | null,
	roles: Record<string, CopilotRoleData> | null,
	engines: object,
}

export type SyncRawMessage = {
	author_id: number,
	chat_id: number,
	date: string,
	forward: {
		id: string,
		userId: number,
		chatTitle: string | null,
		chatType: DialogType,
	}
	id: number,
	isSystem: boolean,
	params: Object | [],
	text: string,
	unread: boolean,
	uuid: string | null,
	viewed: boolean,
	viewedByOthers: boolean
};

export type SyncRawChat = {
	avatar: string,
	color: string,
	counter: number,
	containsCollaber: boolean,
	dateCreate: string,
	description: string,
	dialogId: string,
	diskFolderId: number,
	entityData1: string,
	entityData2: string,
	entityData3: string,
	entityId: string,
	entityType: string,
	entityLink: { type: string, url: string },
	extranet: boolean,
	id: number,
	isNew: boolean,
	lastId: number,
	lastMessageId: number,
	lastMessageViews: {
		countOfViewers: number,
		firstViewers: Array<{
			date: string,
			userId: number,
			userName: string
		}>,
		messageId: number
	},
	managerList: number[],
	markedId: number,
	messageCount: number,
	messageType: string,
	muteList: number[],
	name: string,
	optionalParams: []
	owner: number,
	parentChatId: number,
	parentMessageId: number,
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
	unreadId: number,
	userCounter: number
};

export type SyncRawFile = {
	authorId: number,
	authorName: string,
	chatId: number,
	date: string,
	extension: string,
	id: number,
	image: {
		height: number,
		width: number,
	},
	name: string,
	progress: number,
	size: number,
	status: string,
	type: string,
	urlDownload: string,
	urlPreview: string,
	urlShow: string,
	viewerAttrs: {
		actions: string,
		imChatId: number,
		objectId: string,
		src: string,
		title: string,
		viewer: null | string,
		viewerGroupBy: string,
		viewerResized: null | string,
		viewerType: string
	}
};

export type SyncRawPin = {
	authorId: number,
	chatId: number,
	dateCreate: string,
	id: number,
	messageId: number,
};

export type SyncRawReaction = {
	messageId: number,
	ownReactions?: Array<string>,
	reactionCounters: { [reactionType: string]: number },
	reactionUsers: { [reactionType: string]: Array<number> },
};

export type SyncReaction = SyncRawReaction & {
	dialogId: string,
};

export type SyncRawUser = {
	absent: false | string,
	active: boolean,
	avatar: string,
	avatarHr: string,
	birthday: string,
	bot: boolean,
	botData?: {
		appId: string,
		code: string,
		isHidden: boolean,
		isSupportOpenline: boolean,
		type: string,
	}
	color: string,
	connector: boolean,
	departments: Array<number>,
	desktopLastDate: false | string,
	externalAuthId: string,
	extranet: boolean,
	firstName: string,
	gender: 'M' | 'F',
	id: number,
	idle: false | string,
	lastActivityDate: false | string,
	lastName: string,
	mobileLastDate: false | string,
	name: string,
	network: boolean,
	phones: false | { personal_mobile: string },
	status: string,
	workPosition: string,
	type: string,
};

export type SyncRawShortUser = {
	id: number,
	name: string,
	avatar: string,
	color: string,
	type: string,
};

export type SyncRawRecentItem = {
	chatId: number,
	dateLastActivity: string,
	dateUpdate: string,
	dialogId: string,
	messageId: number,
	pinned: boolean,
	unread: boolean,
};

export type SyncLoadServiceLoadPageResult = {
	hasMore: boolean,
	lastServerDate: string,
	lastId: number | null,
	addedMessageIdList: Array<number>,
	deletedChatIdList: Array<number>,
	deletedMessageIdList: Array<number>,
};
