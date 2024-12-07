declare type DialogRow = {
	dialogId: string,
	chatId: number,
	type: string,
	name: string,
	description: string,
	avatar: string,
	color: string,
	extranet: boolean,
	counter: number,
	userCounter: number,
	lastReadId: number,
	markedId: number,
	lastMessageId: number,
	lastMessageViews: string,
	countOfViewers: number, // FIXME remove this field ( is has lastMessageViews )
	managerList: string,
	readList: string,
	muteList: string,
	owner: number,
	entityType: string,
	entityId: number,
	dateCreate: string,
	public: string,
	code: string,
	diskFolderId: number,
	aiProvider: string,
}

declare type DialogInternalRow = {
	dialogId: string,
	chatId: number,
	wasCompletelySync: string,
}
