import { MessengerModel } from '../base';

declare type SidebarLink = {
	id: linkId,
	messageId: number,
	chatId: number,
	authorId: number,
	dateCreate: Date,
	url: {
		source: string,
		richData: {
			id: number | null,
			description: string | null,
			link: string | null,
			name: string | null,
			previewUrl: string | null,
			type: string | null,
		},
	},
};

declare type SidebarLinkItem = {
	// @ts-ignore
	links: Map<linkId, SidebarLink>,
	hasNextPage: boolean,
	isHistoryLimitExceeded: boolean,
}

declare type chatId = number;
declare type linkId = number;

declare type SidebarLinksModelState = {
	collection: Record<chatId, SidebarLinkItem>,
}

export type SidebarLinksModel = MessengerModel<SidebarLinksModelState>

declare type SidebarLinksModelActions =
	'sidebarModel/sidebarLinksModel/set'
	| 'sidebarModel/sidebarLinksModel/setFromPagination'
	| 'sidebarModel/sidebarLinksModel/delete'
	| 'sidebarModel/sidebarLinksModel/setHistoryLimitExceeded'

declare type SidebarLinksModelMutation =
	'sidebarModel/sidebarLinksModel/set'
	| 'sidebarModel/sidebarLinksModel/delete'
	| 'sidebarModel/sidebarLinksModel/setHasNextPage'
	| 'sidebarModel/sidebarLinksModel/setHistoryLimitExceeded'

declare type SidebarLinksSetActions = 'set' | 'setFromPagination';
declare type SidebarLinksSetData = {
	chatId: chatId,
	// @ts-ignore
	links: Map<linkId, SidebarLink>,
}

declare type SidebarLinksSetHistoryLimitExceededActions = 'setHistoryLimitExceeded';
declare type SidebarLinksSetHistoryLimitExceededData = {
	chatId: chatId,
	isHistoryLimitExceeded: boolean,
}

declare type SidebarLinksDeleteActions = 'delete';
declare type SidebarLinksDeleteData = {
	chatId: chatId,
	id: linkId,
}

declare type SidebarLinksSetHasNextPageActions = 'setHasNextPage';
declare type SidebarLinksSetHasNextPageData = {
	chatId: chatId,
	hasNextPage: boolean,
}
