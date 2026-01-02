import {
	getChatDialogEntity,
	getChannelDialogEntity,
	getCollabDialogEntity,
} from 'humanresources.company-structure.structure-components';
import type { CommunicationDetailed } from 'humanresources.company-structure.utils';
import { ChatLinkDialogDataTestIds, ChannelLinkDialogDataTestIds, CollabLinkDialogDataTestIds } from '../consts';

export const DialogDictionary: Record<string, DialogDictionaryItem> = Object.freeze({
	chat: {
		dialogId: 'hr-department-detail-content-chats-tab-chat-link-dialog',
		title: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_CHAT_LINK_DIALOG_TITLE',
		description: {
			team: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_CHAT_LINK_DIALOG_TEAM_DESC',
			default: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_CHAT_LINK_DIALOG_DESC',
		},
		childrenModeText: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_MODE_CHATS_TEXT',
		dataTestIds: ChatLinkDialogDataTestIds,
		getDialogEntity: (): Object => getChatDialogEntity(),
		getDialogIdFromId: (item: CommunicationDetailed): string => `chat${item.id}`,
		getIdFromDialogId: (item: CommunicationDetailed): number => Number(item.id?.replace('chat', '')),
	},
	channel: {
		dialogId: 'hr-department-detail-content-chats-tab-channel-link-dialog',
		title: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_CHANNEL_LINK_DIALOG_TITLE',
		description: {
			team: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_CHANNEL_LINK_DIALOG_TEAM_DESC',
			default: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_CHANNEL_LINK_DIALOG_DESC',
		},
		childrenModeText: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_MODE_CHANNELS_TEXT',
		dataTestIds: ChannelLinkDialogDataTestIds,
		getDialogEntity: (): Object => getChannelDialogEntity(),
		getDialogIdFromId: (item: CommunicationDetailed): string => `chat${item.id}`,
		getIdFromDialogId: (item: CommunicationDetailed): number => Number(item.id?.replace('chat', '')),
	},
	collab: {
		dialogId: 'hr-department-detail-content-chats-tab-channel-link-dialog',
		title: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_COLLAB_LINK_DIALOG_TITLE',
		description: {
			team: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_COLLAB_LINK_DIALOG_TEAM_DESC',
			default: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_COLLAB_LINK_DIALOG_DESC',
		},
		childrenModeText: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_MODE_COLLABS_TEXT',
		dataTestIds: CollabLinkDialogDataTestIds,
		getDialogEntity: (): Object => getCollabDialogEntity(),
		getDialogIdFromId: (item: CommunicationDetailed): string | number => Number(item.id),
		getIdFromDialogId: (item: CommunicationDetailed): number => Number(item.id),
	},
});

export type DialogDictionaryItem = {
	dialogId: string,
	title: string,
	description: {
		team: string,
		default: string,
	},
	childrenModeText: string,
	dataTestIds: Object,
	getDialogEntity: () => Object,
	getDialogIdFromId: (item: CommunicationDetailed) => string | number,
	getIdFromDialogId: (item: CommunicationDetailed) => number,
};
