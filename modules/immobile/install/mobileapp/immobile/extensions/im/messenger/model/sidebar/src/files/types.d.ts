import { MessengerModel } from '../../../base';

declare type SidebarFile = {
	id: number,
	messageId: number,
	chatId: number,
	authorId: number,
	dateCreate: Date,
	fileId: number
};

declare type SidebarFileSubTypeItem = {
	// @ts-ignore
	items: Map<number, SidebarFile>,
	hasNextPage: boolean,
	isHistoryLimitExceeded: boolean,
	lastLoadFileId: number,
}

declare type chatId = number;
declare type subType = string;

declare type SidebarFilesModelState = {
	collection: Record<chatId, Record<subType, SidebarFileSubTypeItem>>,
}

export type SidebarFilesModel = MessengerModel<SidebarFilesModelState>

declare type SidebarFilesModelActions =
	'sidebarModel/sidebarFilesModel/set'
	| 'sidebarModel/sidebarFilesModel/setFromPagination'
	| 'sidebarModel/sidebarFilesModel/delete'
	| 'sidebarModel/sidebarFilesModel/setHistoryLimitExceeded'
	| 'sidebarModel/sidebarFilesModel/deleteFilesGroupedByChatId'
	| 'sidebarModel/sidebarFilesModel/deleteByChatId'
	;

declare type SidebarFilesModelMutation =
	'sidebarModel/sidebarFilesModel/set'
	| 'sidebarModel/sidebarFilesModel/delete'
	| 'sidebarModel/sidebarFilesModel/setHasNextPage'
	| 'sidebarModel/sidebarFilesModel/setHistoryLimitExceeded'
	| 'sidebarModel/sidebarFilesModel/deleteByChatId'
	;

declare type SidebarFilesSetActions = 'set' | 'setFromPagination';
declare type SidebarFilesSetData = {
	chatId: chatId,
	subType: subType,
	// @ts-ignore
	files: Map<number, SidebarFile>,
}

declare type SidebarFilesSetHistoryLimitExceededActions = 'setHistoryLimitExceeded';
declare type SidebarFilesSetHistoryLimitExceededData = {
	chatId: chatId,
	subType: subType,
	isHistoryLimitExceeded: boolean,
}

declare type SidebarFilesDeleteActions = 'delete';
declare type SidebarFilesDeleteData = {
	chatId: chatId,
	id: number,
}

declare type SidebarFilesDeleteByChatIdActions = 'deleteByChatId';
declare type SidebarFilesDeleteByChatIdData = {
	chatId: chatId,
}

declare type SidebarFilesSetHasNextPageActions = 'setHasNextPage';
declare type SidebarFilesSetHasNextPageData = {
	chatId: chatId,
	subType: subType,
	hasNextPage: boolean,
}
