import { ChatTypes } from 'humanresources.company-structure.utils';
import { AvatarRound, AvatarSquare, AvatarHexagonGuest, AvatarBase } from 'ui.avatar';

export const ItemDictionary: Record<string, ItemDictionaryType> = Object.freeze({
	[ChatTypes.chat]: {
		parentTeamHint: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_PARENT_TEAM_CHAT_HINT',
		subtitleForTeam: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_CHAT_OF_TEAM_MSGVER_2',
		parentDepartmentOfDepartmentHint: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_PARENT_DEPARTMENT_CHAT_OF_DEPARTMENT_HINT',
		parentDepartmentOfTeamHint: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_PARENT_DEPARTMENT_CHAT_OF_TEAM_HINT',
		subtitleForDepartment: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_CHAT_OF_DEPARTMENT_MSGVER_2',
		getAvatar: (avatarOptions: Object): AvatarBase => new AvatarRound(avatarOptions),
		dataTestIdPrefix: 'hr-department-content_chats-tab__list_chat-item-',
	},
	[ChatTypes.channel]: {
		parentTeamHint: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_PARENT_TEAM_CHANNEL_HINT',
		subtitleForTeam: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_CHANNEL_OF_TEAM_MSGVER_2',
		parentDepartmentOfDepartmentHint: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_PARENT_DEPARTMENT_CHANNEL_OF_DEPARTMENT_HINT',
		parentDepartmentOfTeamHint: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_PARENT_DEPARTMENT_CHANNEL_OF_TEAM_HINT',
		subtitleForDepartment: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_CHANNEL_OF_DEPARTMENT_MSGVER_2',
		getAvatar: (avatarOptions: Object): AvatarBase => new AvatarSquare(avatarOptions),
		dataTestIdPrefix: 'hr-department-content_chats-tab__list_chat-item-',
	},
	[ChatTypes.collab]: {
		parentTeamHint: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_PARENT_TEAM_COLLAB_HINT',
		subtitleForTeam: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_COLLAB_OF_TEAM_MSGVER_2',
		parentDepartmentOfDepartmentHint: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_PARENT_DEPARTMENT_COLLAB_OF_DEPARTMENT_HINT',
		parentDepartmentOfTeamHint: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_PARENT_DEPARTMENT_COLLAB_OF_TEAM_HINT',
		subtitleForDepartment: 'HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_COLLAB_OF_DEPARTMENT_MSGVER_2',
		getAvatar: (avatarOptions: Object): AvatarBase => new AvatarHexagonGuest(avatarOptions),
		dataTestIdPrefix: 'hr-department-content_chats-tab__list_collab-item-',
	},
});

export type ItemDictionaryType = {
	noAccessHint: string,
	parentTeamHint: string,
	subtitleForTeam: string,
	parentDepartmentOfDepartmentHint: string,
	parentDepartmentOfTeamHint: string,
	subtitleForDepartment: string,
	getAvatar: string,
	dataTestIdPrefix: string,
};
