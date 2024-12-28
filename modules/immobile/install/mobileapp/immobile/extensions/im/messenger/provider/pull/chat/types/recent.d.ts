import { RawChat, RawFile, RawMessage, RawUser } from '../../../service/src/types/sync-list-result';

declare type RecentUpdateParams = {
	additionalMessages: RawMessage[],
	chat: RawChat,
	counter: number,
	files: RawFile[],
	lastActivityDate: string,
	messages: RawMessage[],
	users: RawUser[],
};

declare type UserShowInRecentParams = {
	items: UserShowInRecentItem[],
};

type UserShowInRecentItem = {
	user: RawUser,
	date: string,
};
