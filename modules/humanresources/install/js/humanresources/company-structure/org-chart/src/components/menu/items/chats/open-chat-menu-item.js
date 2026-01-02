import type { CommunicationDetailed } from 'humanresources.company-structure.utils';
import { getColorCode, ChatTypes } from 'humanresources.company-structure.utils';
import { Main } from 'ui.icon-set.api.core';
import { AbstractMenuItem } from '../abstract-menu-item';
import { PermissionCheckerClass } from 'humanresources.company-structure.permission-checker';
import { MenuActions } from '../../menu-actions';
import { Loc } from 'main.core';

/**
 * Menu item for opening a chat or channel.
 * Displays appropriate title, and description based on chat type.
 * Checks permissions using hasAccess property of the chat.
 */
export class OpenChatMenuItem extends AbstractMenuItem
{
	chat: CommunicationDetailed;

	static Dictionary = Object.freeze({
		[ChatTypes.chat]: {
			title: 'HUMANRESOURCES_COMPANY_STRUCTURE_CHAT_LIST_ACTION_MENU_OPEN_CHAT_TITLE',
			description: 'HUMANRESOURCES_COMPANY_STRUCTURE_CHAT_LIST_ACTION_MENU_OPEN_CHAT_DESCRIPTION',
		},
		[ChatTypes.channel]: {
			title: 'HUMANRESOURCES_COMPANY_STRUCTURE_CHAT_LIST_ACTION_MENU_OPEN_CHANNEL_TITLE',
			description: 'HUMANRESOURCES_COMPANY_STRUCTURE_CHAT_LIST_ACTION_MENU_OPEN_CHANNEL_DESCRIPTION',
		},
		[ChatTypes.collab]: {
			title: 'HUMANRESOURCES_COMPANY_STRUCTURE_CHAT_LIST_ACTION_MENU_OPEN_COLLAB_TITLE',
			description: 'HUMANRESOURCES_COMPANY_STRUCTURE_CHAT_LIST_ACTION_MENU_OPEN_COLLAB_DESCRIPTION',
		},
	});

	constructor(entityType: string, chat: CommunicationDetailed)
	{
		const { title, description } = OpenChatMenuItem.Dictionary[chat.type] || {};

		super({
			id: MenuActions.openChat,
			title: Loc.getMessage(title),
			description: Loc.getMessage(description),
			bIcon: {
				name: Main.EDIT_PENCIL,
				size: 20,
				color: getColorCode('paletteBlue50'),
			},
			permissionAction: null,
			dataTestId: 'hr-department-detail-content__chat-list_open-chat',
		});

		this.entityType = entityType;
		this.chat = chat;
	}

	hasPermission(permissionChecker: PermissionCheckerClass, entityId: number): boolean
	{
		return this.chat.hasAccess;
	}
}
