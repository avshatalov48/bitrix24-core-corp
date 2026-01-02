import {RecentItemData} from '../../../../controller/recent/copilot/types/recent';
import {UsersModelState} from '../../../../model/users/src/types';
import {DialoguesModelState} from '../../../../model/dialogues/src/types';
import {RawMessage, RawFile} from '../../sync/types/sync-list-result';
import {ChannelRecentItemData} from '../../../../controller/recent/channel/types/recent';
import {channelChatId, commentChatId} from '../../../../model/comment/src/types';
import {
	ChatsCopilotDataItem,
	CopilotRoleData,
	MessageCopilotDataItem
} from '../../../../model/dialogues/src/copilot/types';
import {PlanLimits} from '../../../../lib/params/types/params';
import {messagesAutoDeleteConfigs} from '../../../pull/base/types/message';
import {AnchorModelState} from "../../../../model/anchor/src/types";
import {imV2CollabTailResult} from "../../../../controller/recent/collab/types/recent";

declare type immobileTabsLoadCommonResult = {
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
		collabUnread: number[],
		copilot: Record<number, number>,
		copilotUnread: number[],
		tasksTask: Record<number, number>,
		tasksTaskUnread: number[],
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
	mobileRevision: number,
	portalCounters: {
		result: Object,
		time: number,
	},
	serverTime: string,
	userData: UsersModelState,
}

declare type immobileTabChatLoadResult = Partial<immobileTabsLoadCommonResult> & {
	departmentColleagues?: unknown[] | null,
	recentList: immobileTabChatLoadResultRecentList,
	tariffRestriction?: PlanLimits,
	activeCalls: [],
	anchors: AnchorModelState[],
}

declare type immobileTabChatLoadResultRecentList = {
	additionalMessages: Array<RawMessage>,
	birthdayList: unknown[],
	chats: DialoguesModelState[],
	copilot: immobileTabLoadResultCopilotData | null,
	files: RawFile[],
	hasMore: boolean,
	hasNextPage: boolean,
	items: RecentItemData[],
	messagesAutoDeleteConfigs: Array<messagesAutoDeleteConfigs>,
}

declare type immobileTabChannelLoadResult = Partial<immobileTabsLoadCommonResult> & {
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
}

declare type immobileTabCopilotLoadResultV2 = {
	copilotList: immobileTabCopilotLoadResultCopilotList,
}

declare type immobileTabChatsLoadResultV2 = {
	chatsList: immobileTabChatsLoadResultChatsList,
}

declare type immobileTabCollabLoadResultV2 = {
	collabList: immobileTabCollabLoadResultCollabList,
}

declare type immobileTabCopilotLoadResultCopilotList = {
	birthdayList: unknown[],
	copilot: immobileTabLoadResultCopilotData | null,
	hasMore: boolean,
	hasMorePages: boolean,
	items: RecentItemData[],
}

declare type immobileTabChatsLoadResultChatsList = {
	birthdayList: unknown[],
	copilot: immobileTabLoadResultCopilotData | null,
	hasMore: boolean,
	hasMorePages: boolean,
	items: RecentItemData[],
	messagesAutoDeleteConfigs: Array<MessagesAutoDeleteConfigs>,
}

declare type immobileTabCollabLoadResultCollabList = imV2CollabTailResult

declare type immobileTabCopilotLoadResult = Partial<immobileTabsLoadCommonResult> & {
	recentList: {
		birthdayList: unknown[], // TODO: concrete type
		copilot: immobileTabLoadResultCopilotData | null,
		hasMore: boolean,
		hasNextPage: boolean,
		items: RecentItemData[],
	},
}

declare type MessengerInitActionData = {
	methodList: Array<string>,
	options?: {
		siteId: string,
	}
}


declare type immobileTabLoadResultCopilotData = {
	chats: ChatsCopilotDataItem[],
	messages: MessageCopilotDataItem[],
	recommendedRoles: string[],
	roles: Record<string, CopilotRoleData>,
};
