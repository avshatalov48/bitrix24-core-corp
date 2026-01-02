import { MessengerModel } from '../../../base';
import { DialogId } from '../../../../types/common';
import { DialogType } from '../../../dialogues/src/types';
import { UserRole } from '../../../users/src/types';

type chatId = number;

declare type SidebarCommonChat = {
	avatar: string;
	color: string;
	dateMessage: string;
	description: string;
	dialogId: DialogId;
	diskFolderId: number;
	entityId: string;
	entityType: string;
	extranet: boolean;
	id: number;
	isNew: boolean;
	name: string;
	owner: number;
	parentChatId: number;
	parentMessageId: number;
	role: UserRole;
	type: DialogType;
};

declare type SidebarCommonChatItem = {
	chats: Map<chatId, SidebarCommonChat>,
	hasNextPage: boolean,
}

declare type SidebarCommonChatMap = Map<chatId, SidebarCommonChat>;

declare type SidebarCommonChatsModelState = {
	collection: Record<chatId, SidebarCommonChatItem>,
}

export type SidebarCommonChatsModel = MessengerModel<SidebarCommonChatsModelState>

declare type sidebarCommonChatsModelActions =
	'sidebarModel/sidebarCommonChatsModel/set'
	| 'sidebarModel/sidebarCommonChatsModel/setFromPagination'
	| 'sidebarModel/sidebarCommonChatsModel/delete'
	| 'sidebarModel/sidebarCommonChatsModel/deleteByChatId'

declare type sidebarCommonChatsModelMutation =
	'sidebarModel/sidebarCommonChatsModel/set'
	| 'sidebarModel/sidebarCommonChatsModel/delete'
	| 'sidebarModel/sidebarCommonChatsModel/deleteByChatId'
	| 'sidebarModel/sidebarCommonChatsModel/setHasNextPage'

declare type SidebarCommonChatsSetActions = 'set' | 'setFromPagination';
declare type SidebarCommonChatsSetData = {
	chatId: chatId,
	chats: Map<chatId, SidebarCommonChat>,
}

declare type SidebarCommonChatsDeleteActions = 'delete';
declare type SidebarCommonChatsDeleteData = {
	chatId: chatId,
	id: chatId,
}

declare type SidebarCommonChatsDeleteByChatIdActions = 'deleteByChatId';
declare type SidebarCommonChatsDeleteByChatIdData = {
	chatId: chatId,
}

declare type SidebarLinksSetHasNextPageActions = 'setHasNextPage';
declare type SidebarLinksSetHasNextPageData = {
	chatId: chatId,
	hasNextPage: boolean,
}
