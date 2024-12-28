declare type SyncListResult = {
	addedChats: Array<RawChat>,
	messages: {
		additionalMessages: Array<any>,
		files: Array<RawFile>,
		messages: Array<RawMessage>,
		reactions: Array<RawReaction>,
		reminders: [],
		users: Array<RawUser>,
		usersShort: Array<RawShortUser>,
	},
	updatedMessages: {
		additionalMessages: Array<any>,
		files: Array<RawFile>,
		messages: Array<RawMessage>,
		reactions: Array<RawReaction>,
		reminders: [],
		users: Array<RawUser>,
		usersShort: Array<RawShortUser>,
	},
	addedPins: {
		additionalMessages: Array<RawMessage>,
		files: Array<RawFile>,
		reactions: Array<RawReaction>,
		pins: Array<RawPin>,
		users: Array<RawUser>,
	},
	addedRecent: Array<RawRecentItem>,
	completeDeletedMessages: Record<number, number>,
	deletedChats: Record<number, number>,
	completeDeletedChats: Record<number, number>,
	deletedMessages: [],
	deletedPins: [] | Record<number, number>,
	dialogIds: Record<string, string>,
	hasMore: boolean,
	lastId: number,
	lastServerDate: string,
}

export type RawMessage = {
	author_id: number,
	chat_id: number,
	date: string,
	id: number,
	isSystem: boolean,
	params: Object,
	replaces: [],
	text: string,
	unread: boolean,
	uuid: string | null,
	viewed: boolean,
	viewedByOthers: boolean
};

export type RawChat = {
	avatar: string,
	color: string,
	counter: number,
	dateCreate: string,
	description: string,
	dialogId: string,
	diskFolderId: number,
	entityData1: string,
	entityData2: string,
	entityData3: string,
	entityId: string,
	entityType: string,
	extranet: boolean,
	id: number,
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
	owner: number,
	public: string,
	restrictions: RawRestrictions,
	role: string,
	type: string,
	unreadId: number,
    userCounter: number
    parentChatId: number,
    parentMessageId: number,
    parent_chat_id: number, // Pull only
    parent_message_id: number, // Pull only
};

export type RawRestrictions = {
	avatar: boolean,
	call: boolean,
	extend: boolean,
	leave: boolean,
	leaveOwner: boolean,
	mute: boolean,
	rename: boolean,
	send: boolean,
	userList: boolean,
};

export type RawFile = {
	authorId: number,
	authorName: string,
	chatId: number,
	date: string,
	extension: string,
	id: number,
	image: boolean,
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
		viewer: null,
		viewerGroupBy: string,
		viewerType: string
	}
};

export type RawPin = {
	id: number,
	messageId: number,
	chatId: number,
	authorId: number,
	dateCreate: string,
};

export type RawReaction = {
	messageId: number,
	reactionCounters: {[reactionType: string]: number},
	reactionUsers: {[reactionType: string]: number[]},
	ownReactions?: []
};

export type RawUser = {
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
	departments: number[],
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
	phones: false | number[],
	status: string,
	workPosition: string
};

export type RawShortUser = {
	id: number,
	name: string,
	avatar: string
};

export type RawRecentItem = {
	id: string, // dialogId
	chat_id: number,
	type: string,
	title: string,
	avatar: {
		url: string,
		color: string
	},
	counter: number,
	pinned: boolean,
	unread: boolean,
	last_id: number,
	date_update: string,
	date_last_activity: string,
	message: {
		attach: boolean,
		author_id: number,
		date: string,
		file: boolean | {},
		id: number,
		status: string,
		text: string,
		uuid: string,
	},
	chat: {},
	user: {},
	has_reminder: boolean,
	options: [],
};

export type SyncLoadServiceLoadPageResult = {
	hasMore: boolean,
	lastId: number,
	lastServerDate: string,
	addedMessageIdList: Array<number>,
	deletedChatIdList: Array<number>,
	deletedMessageIdList: Array<number>,
};
