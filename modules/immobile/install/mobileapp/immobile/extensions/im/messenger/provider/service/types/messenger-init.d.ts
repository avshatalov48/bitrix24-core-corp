import { RecentItemData } from '../../../controller/recent/copilot/types/recent';
import { UsersModelState } from '../../../model/types/users';
import { DialoguesModelState } from '../../../model/types/dialogues';
import { RawMessage, RawFile } from '../src/types/sync-list-result';
import { ChannelRecentItemData } from '../../../controller/recent/channel/types/recent';
import { channelChatId, commentChatId } from '../../../model/types/comment';
import { ChatsCopilotDataItem, CopilotRoleData, MessageCopilotDataItem } from '../../../model/types/dialogues/copilot';
import { PlanLimits } from '../../../lib/params/types/params';

declare type immobileTabChatLoadResult = {
	departmentColleagues: unknown[] | null,
	desktopStatus: {
		isOnline: boolean,
		version: number,
	},
	imCounters: {
		channelComment: Record<channelChatId, Record<commentChatId, number>>,
		chat: Record<string, number>,
		chatMuted: number[],
		chatUnread: number[],
		collab: Record<number, number>,
		copilot: Record<number, number>,
		lines: unknown[],
		type: {
			all: number,
			chat: number,
			collab: number,
			copilot: number,
			lines: number,
			notify: number,
		},
	},
	portalCounters: {
		result: Object,
		time: number,
	},
	recentList: {
		additionalMessages: Array<RawMessage>,
		birthdayList: unknown[], // TODO: concrete type
		chats: DialoguesModelState[],
		copilot: null,
		files: RawFile[],
		hasMore: boolean,
		hasNextPage: boolean,
		items: RecentItemData[],
	},
	userData: UsersModelState,
	mobileRevision: number,
	serverTime: string,
	tariffRestriction: PlanLimits,
}

declare type immobileTabChannelLoadResult = {
	desktopStatus: {
		isOnline: boolean,
		version: number,
	},
	imCounters: {
		channelComment: Record<channelChatId, Record<commentChatId, number>>,
		chat: Record<string, number>,
		chatMuted: number[],
		chatUnread: number[],
		collab: Record<number, number>,
		copilot: Record<number, number>,
		lines: unknown[],
		type: {
			all: number,
			chat: number,
			collab: number,
			copilot: number,
			lines: number,
			notify: number,
		},
	},
	portalCounters: {
		result: Object,
		time: number,
	},
	recentList: {
		additionalMessages: Array<RawMessage>,
		birthdayList: unknown[], // TODO: concrete type
		chats: DialoguesModelState[],
		copilot: null,
		files: RawFile[],
		hasNextPage: boolean,
		messages: RawMessage[],
		recentItems: ChannelRecentItemData,
		reminders: unknown[],
		users: UsersModelState[],
	},
	userData: UsersModelState,
	mobileRevision: number,
	serverTime: string,
}

declare type immobileTabCopilotLoadResult = {
	desktopStatus: {
		isOnline: boolean,
		version: number,
	},
	imCounters: {
		channelComment: Record<channelChatId, Record<commentChatId, number>>,
		chat: Record<string, number>,
		chatMuted: number[],
		chatUnread: number[],
		collab: Record<number, number>,
		copilot: Record<number, number>,
		lines: unknown[],
		type: {
			all: number,
			chat: number,
			collab: number,
			copilot: number,
			lines: number,
			notify: number,
		},
	},
	portalCounters: {
		result: Object,
		time: number,
	},
	recentList: {
		birthdayList: unknown[], // TODO: concrete type
		copilot: {
			chats: ChatsCopilotDataItem[],
			messages: MessageCopilotDataItem[],
			recommendedRoles: string[],
			roles: Record<string, CopilotRoleData>,
		},
		hasMore: boolean,
		hasNextPage: boolean,
		items: RecentItemData[],
	},
	mobileRevision: number,
	serverTime: string,
}
