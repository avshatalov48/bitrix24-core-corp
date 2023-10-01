export type MessagesModelState = {
	id: number | string,
	templateId: string,
	chatId: number,
	authorId: number,
	date: Date,
	text: string,
	params: object,
	replaces: Array<Object>,
	files: Array<number | string>,
	unread: boolean,
	viewed: boolean,
	viewedByOthers: boolean,
	sending: boolean,
	error: boolean,
	retry: boolean,
	audioPlaying: boolean,
	playingTime: number,
}

export type MessagesModelActions =
	'messagesModel/forceUpdateByChatId'
	| 'messagesModel/store'
	| 'messagesModel/add'
	| 'messagesModel/setPinned'
	| 'messagesModel/updateWithId'
	| 'messagesModel/update'
	| 'messagesModel/delete'
	| 'messagesModel/setReaction'
	| 'messagesModel/addReaction'
	| 'messagesModel/removeReaction'
	| 'messagesModel/readMessages'
	| 'messagesModel/setViewedByOthers'

export type MessagesModelMutation =
	'messagesModel/setChatCollection'
	| 'messagesModel/store'
	| 'messagesModel/setPinned'
	| 'messagesModel/updateWithId'
	| 'messagesModel/update'
	| 'messagesModel/delete'
	| 'messagesModel/clearCollection'
