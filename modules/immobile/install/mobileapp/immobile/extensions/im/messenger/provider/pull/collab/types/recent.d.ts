import { RawChat, RawFile, RawMessage, RawUser } from '../../../service/src/types/sync-list-result';
import { RecentConfigSections } from '../../base/types/recent';

declare type RecentUpdateParams = {
	additionalMessages: RawMessage[],
	chat: RawChat,
	counter: number,
	files: RawFile[],
	lastActivityDate: string,
	messages: RawMessage[],
	users: RawUser[],
	counterType: string,
	recentConfig: {
		chatId: number,
		sections: RecentConfigSections,
	},
};
