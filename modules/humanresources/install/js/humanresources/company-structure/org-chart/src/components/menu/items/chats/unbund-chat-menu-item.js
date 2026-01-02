import type { CommunicationDetailed } from 'humanresources.company-structure.utils';
import { EntityTypes, getColorCode, ChatTypes } from 'humanresources.company-structure.utils';
import { Main } from 'ui.icon-set.api.core';
import { PermissionActions, PermissionCheckerClass } from 'humanresources.company-structure.permission-checker';
import { AbstractMenuItem } from '../abstract-menu-item';
import { MenuActions } from '../../menu-actions';
import { Loc } from 'main.core';

/**
 * Menu item for unbinding a chat or channel from a team or department.
 * Displays appropriate title, description, and permission requirements
 * based on the entity type and chat type.
 */
export class UnbindChatMenuItem extends AbstractMenuItem
{
	chat: CommunicationDetailed;

	static Dictionary = Object.freeze({
		[EntityTypes.team]: {
			[ChatTypes.chat]: {
				title: 'HUMANRESOURCES_COMPANY_STRUCTURE_CHAT_LIST_ACTION_MENU_UNBIND_TEAM_CHAT_TITLE',
				description: 'HUMANRESOURCES_COMPANY_STRUCTURE_CHAT_LIST_ACTION_MENU_UNBIND_TEAM_CHAT_DESCRIPTION',
				permissionAction: PermissionActions.teamChatEdit,
			},
			[ChatTypes.channel]: {
				title: 'HUMANRESOURCES_COMPANY_STRUCTURE_CHAT_LIST_ACTION_MENU_UNBIND_TEAM_CHANNEL_TITLE',
				description: 'HUMANRESOURCES_COMPANY_STRUCTURE_CHAT_LIST_ACTION_MENU_UNBIND_TEAM_CHANNEL_DESCRIPTION',
				permissionAction: PermissionActions.teamChannelEdit,
			},
			[ChatTypes.collab]: {
				title: 'HUMANRESOURCES_COMPANY_STRUCTURE_CHAT_LIST_ACTION_MENU_UNBIND_TEAM_COLLAB_TITLE',
				description: 'HUMANRESOURCES_COMPANY_STRUCTURE_CHAT_LIST_ACTION_MENU_UNBIND_TEAM_COLLAB_DESCRIPTION',
				permissionAction: PermissionActions.teamCollabEdit,
			},
		},
		[EntityTypes.department]: {
			[ChatTypes.chat]: {
				title: 'HUMANRESOURCES_COMPANY_STRUCTURE_CHAT_LIST_ACTION_MENU_UNBIND_DEPARTMENT_CHAT_TITLE',
				description: 'HUMANRESOURCES_COMPANY_STRUCTURE_CHAT_LIST_ACTION_MENU_UNBIND_DEPARTMENT_CHAT_DESCRIPTION',
				permissionAction: PermissionActions.departmentChatEdit,
			},
			[ChatTypes.channel]: {
				title: 'HUMANRESOURCES_COMPANY_STRUCTURE_CHAT_LIST_ACTION_MENU_UNBIND_DEPARTMENT_CHANNEL_TITLE',
				description: 'HUMANRESOURCES_COMPANY_STRUCTURE_CHAT_LIST_ACTION_MENU_UNBIND_DEPARTMENT_CHANNEL_DESCRIPTION',
				permissionAction: PermissionActions.departmentChannelEdit,
			},
			[ChatTypes.collab]: {
				title: 'HUMANRESOURCES_COMPANY_STRUCTURE_CHAT_LIST_ACTION_MENU_UNBIND_DEPARTMENT_COLLAB_TITLE',
				description: 'HUMANRESOURCES_COMPANY_STRUCTURE_CHAT_LIST_ACTION_MENU_UNBIND_DEPARTMENT_COLLAB_DESCRIPTION',
				permissionAction: PermissionActions.departmentCollabEdit,
			},
		},
	});

	constructor(entityType: string, chat: CommunicationDetailed)
	{
		const { title, description, permissionAction } = (UnbindChatMenuItem.Dictionary[entityType][chat.type]) || {};

		super({
			id: MenuActions.unbindChat,
			title: Loc.getMessage(title),
			description: Loc.getMessage(description),
			bIcon: {
				name: Main.TRASH_BIN,
				size: 20,
				color: getColorCode('paletteRed40'),
			},
			permissionAction,
			dataTestId: 'hr-department-detail-content__chat-list_unbind-chat',
		});

		this.chat = chat;
	}

	hasPermission(permissionChecker: PermissionCheckerClass, entityId: number): boolean
	{
		return !this.chat.originalNodeId
			&& this.chat.hasAccess
			&& permissionChecker.hasPermission(this.permissionAction, entityId)
		;
	}
}
