export type CommunicationDetailed = {
	id: number,
	avatar: string,
	color: string,
	dialogId: string,
	isExtranet: boolean,
	originalNodeId: ?number,
	hasAccess: boolean,
	subtitle: string,
	title: string,
	type: string,
};

export type ChatListResponse = {
	channels: CommunicationDetailed[],
	chats: CommunicationDetailed[],
	collabs: CommunicationDetailed[],
	channelsNoAccess: number,
	chatsNoAccess: number,
	collabsNoAccess: number,
}
