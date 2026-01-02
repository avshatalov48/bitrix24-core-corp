import type { ManagementDialogDataTestIds } from 'humanresources.company-structure.structure-components';
import { Main } from 'ui.icon-set.api.core';
import { Loc } from 'main.core';

export const ChatsMenuOption = Object.freeze({
	linkChat: 'linkChat',
	linkChannel: 'linkChannel',
	linkCollab: 'linkCollab',
});

export const ChatsMenuLinkChat = Object.freeze({
	id: ChatsMenuOption.linkChat,
	title: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_CHAT_LIST_LINK_BUTTON_TITLE'),
	description: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_CHAT_LIST_LINK_BUTTON_DESC'),
	bIcon: {
		name: Main.CHAT_MESSAGE,
		size: 20,
		colorTokenName: 'paletteBlue50',
	},
	dataTestId: 'hr-department-content_chats-tab__chat-list-action-link',
});

export const ChatsMenuLinkChannel = Object.freeze({
	id: ChatsMenuOption.linkChannel,
	title: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_CHANNEL_LIST_LINK_BUTTON_TITLE'),
	description: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_CHANNEL_LIST_LINK_BUTTON_DESC'),
	bIcon: {
		name: Main.SPEAKER_MOUTHPIECE,
		size: 20,
		colorTokenName: 'paletteBlue50',
	},
	dataTestId: 'hr-department-content_chats-tab__channel-list-action-link',
});

export const ChatsMenuLinkCollab = Object.freeze({
	id: ChatsMenuOption.linkCollab,
	title: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_COLLAB_LIST_LINK_BUTTON_TITLE'),
	description: Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_COLLAB_LIST_LINK_BUTTON_DESC'),
	bIcon: {
		name: Main.COLLAB,
		size: 20,
		colorTokenName: 'paletteBlue50',
	},
	dataTestId: 'hr-department-content_chats-tab__collab-list-action-link',
});

export const ChatListDataTestIds = Object.freeze({
	containerDataTestId: 'hr-department-content_chats-tab__chat-list-container',
	listActionButtonDataTestId: 'hr-department-content_chats-tab__chat-list-action-button',
	listActonMenuDataTestId: 'hr-department-content_chats-tab__chat-list-action-menu-container',
	listCounterDataTestId: 'hr-department-content_chats-tab__chat-list-counter',
});

export const ChannelListDataTestIds = Object.freeze({
	containerDataTestId: 'hr-department-content_chats-tab__channel-list-container',
	listActionButtonDataTestId: 'hr-department-content_chats-tab__channel-list-action-button',
	listActonMenuDataTestId: 'hr-department-content_chats-tab__channel-list-action-menu-container',
	listCounterDataTestId: 'hr-department-content_chats-tab__channel-list-counter',
});

export const CollabListDataTestIds = Object.freeze({
	containerDataTestId: 'hr-department-content_chats-tab__collab-list-container',
	listActionButtonDataTestId: 'hr-department-content_chats-tab__collab-list-action-button',
	listActonMenuDataTestId: 'hr-department-content_chats-tab__collab-list-action-menu-container',
	listCounterDataTestId: 'hr-department-content_chats-tab__collab-list-counter',
});

export const ChatLinkDialogDataTestIds: {
	...ManagementDialogDataTestIds,
	addWithChildrenDataTestId: string,
} = Object.freeze({
	containerDataTestId: 'hr-department-content_chats-tab__link-chat-container',
	confirmButtonDataTestId: 'hr-department-content_chats-tab__link-chat-confirm-button',
	cancelButtonDataTestId: 'hr-department-content_chats-tab__link-chat-cancel-button',
	closeButtonDataTestId: 'hr-department-content_chats-tab__link-chat-close-button',
	addWithChildrenDataTestId: 'hr-department-content_chats-tab__link-chat-add-with-children',
});

export const ChannelLinkDialogDataTestIds: {
	...ManagementDialogDataTestIds,
	addWithChildrenDataTestId: string,
} = Object.freeze({
	containerDataTestId: 'hr-department-content_chats-tab__link-channel-container',
	confirmButtonDataTestId: 'hr-department-content_chats-tab__link-channel-confirm-button',
	cancelButtonDataTestId: 'hr-department-content_chats-tab__link-channel-cancel-button',
	closeButtonDataTestId: 'hr-department-content_chats-tab__link-channel-close-button',
	addWithChildrenDataTestId: 'hr-department-content_chats-tab__link-channel-add-with-children',
});

export const CollabLinkDialogDataTestIds: {
	...ManagementDialogDataTestIds,
	addWithChildrenDataTestId: string,
} = Object.freeze({
	containerDataTestId: 'hr-department-content_chats-tab__link-collab-container',
	confirmButtonDataTestId: 'hr-department-content_chats-tab__link-collab-confirm-button',
	cancelButtonDataTestId: 'hr-department-content_chats-tab__link-collab-cancel-button',
	closeButtonDataTestId: 'hr-department-content_chats-tab__link-collab-close-button',
	addWithChildrenDataTestId: 'hr-department-content_chats-tab__link-collab-add-with-children',
});
