export enum DialogType {
	user = 'user',
	chat = 'chat',
	open = 'open',
	general = 'general',
	videoconf = 'videoconf',
	announcement = 'announcement',
	call = 'call',
	support24Notifier = 'support24Notifier',
	support24Question = 'support24Question',
	crm = 'crm',
	sonetGroup = 'sonetGroup',
	calendar = 'calendar',
	tasks = 'tasks',
	thread = 'thread',
	mail = 'mail',
}

export type DialoguesModelState = {
    dialogId: string,
    chatId: number,
    type: DialogType,
    name: string,
    description: string,
    avatar: string,
    color: string,
    extranet: boolean,
    counter: number,
    userCounter: number,
    participants: Array<any>,
    lastReadId: number,
    markedId: number,
    lastMessageId: number,
    lastMessageViews: LastMessageViews,
    savedPositionMessageId: number,
    managerList: Array<any>, //todo concrete type
    readList: Array<any>, //todo concrete type
    writingList: Array<WritingUserData>,
    muteList: Array<any>, //todo concrete type
    textareaMessage: string,
    quoteId: number,
    owner: number,
    entityType: string,
    entityId: string,
    dateCreate: Date | null,
    public: {
        code: string,
        link: string
    },
    inited: boolean,
    loading: boolean,
    hasPrevPage: boolean,
    hasNextPage: boolean,
};

export type LastMessageViews = {
	lastMessageViews: {
		countOfViewers: number,
		firstViewer: {
			date: string
			userId: number
			userName: string
		} | null,
		messageId: number,
	}
	isGroupDialog?:boolean,
}

export type WritingUserData = {
	userId: number
	userName: string
}


export type DialoguesModelActions =
	'dialoguesModel/set'
	| 'dialoguesModel/add'
	| 'dialoguesModel/update'
	| 'dialoguesModel/delete'
	| 'dialoguesModel/updateWritingList'
	| 'dialoguesModel/clearLastMessageViews'
	| 'dialoguesModel/incrementLastMessageViews'
	| 'dialoguesModel/setLastMessageViews'
	| 'dialoguesModel/decreaseCounter'
    | 'dialoguesModel/clearAllCounters'
    | 'dialoguesModel/addParticipants'
    | 'dialoguesModel/removeParticipants'

export type DialoguesModelMutation =
	'dialoguesModel/add'
	| 'dialoguesModel/update'
	| 'dialoguesModel/delete'