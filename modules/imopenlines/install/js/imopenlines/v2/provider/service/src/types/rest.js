import type { RawFile, RawMessage, RawUser, RawChat } from 'im.v2.provider.service';
import type { StatusGroupName } from 'imopenlines.v2.const';

export type RawSession = {
	chatId: number,
	id: number,
	operatorId: number,
	status: StatusGroupName,
	queueId: number,
	pinned: boolean,
	isClosed: boolean,
}

export type RawRecentItem = {
	chatId: number,
	dialogId: string,
	messageId: number,
	sessionId: number
}

export type RawQueue = {
	id: number,
	lineName: string,
	type: string,
	isActive: boolean,
}

export type RecentRestResult = {
	users: RawUser[],
	chats: RawChat[],
	messages: RawMessage[],
	files: RawFile[],
	recentItems: RawRecentItem[],
	sessions: RawSession[],
	additionalMessages: RawMessage[],
}
