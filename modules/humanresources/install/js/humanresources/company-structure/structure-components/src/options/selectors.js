import { EntityTypes } from 'humanresources.company-structure.utils';
import { Loc } from 'main.core';
import type { TabOptions } from 'ui.entity-selector';

export const getChatDialogEntity = function(): Object {
	return {
		id: 'im-chat-only',
		dynamicLoad: true,
		dynamicSearch: true,
		filters: [
			{
				id: 'im.chatOnlyDataFilter',
				options: {
					includeSubtitle: true,
				},
			},
		],
		tagOptions: {
			default: {
				textColor: '#11A9D9',
				bgColor: '#D3F4FF',
				avatar: '/bitrix/js/humanresources/company-structure/structure-components/src/images/selectors/bind-chat-chat-tag.svg',
			},
		},
		itemOptions: {
			default: {
				avatar: '/bitrix/js/humanresources/company-structure/structure-components/src/images/selectors/bind-chat-chat-item.svg',
			},
		},
		options: {
			searchChatTypes: ['O', 'C'],
		},
	};
};

export const getChannelDialogEntity = function(): Object {
	return {
		id: 'im-chat-only',
		filters: [
			{
				id: 'im.chatOnlyDataFilter',
				options: {
					includeSubtitle: true,
				},
			},
		],
		dynamicLoad: true,
		dynamicSearch: true,
		tagOptions: {
			default: {
				textColor: '#8DBB00',
				bgColor: '#EAF6C3',
				avatar: '/bitrix/js/humanresources/company-structure/structure-components/src/images/selectors/bind-chat-channel-tag.svg',
				avatarOptions: {
					borderRadius: '50%',
				},
			},
		},
		itemOptions: {
			default: {
				avatar: '/bitrix/js/humanresources/company-structure/structure-components/src/images/selectors/bind-chat-channel-item.svg',
				avatarOptions: {
					borderRadius: '6px',
				},
			},
		},
		options: {
			searchChatTypes: ['N', 'J'],
		},
	};
};

export const getCollabDialogEntity = function(): Object {
	return {
		id: 'project',
		dynamicLoad: true,
		dynamicSearch: true,
		options: {
			type: ['collab'],
			createProjectLink: false,
			checkCollabInviteOption: true,
		},
		itemOptions: {
			collab: {
				supertitle: null,
				subtitle: Loc.getMessage('HUMANRESOURCES_STRUCTURE_COMPONENTS_COLLAB_SUPERTITLE'),
				textColor: '#535c69',
			},
		},
		tagOptions: {
			default: {
				textColor: '#207976',
				bgColor: '#ade7e4',
				avatar: '/bitrix/js/socialnetwork/entity-selector/src/images/collab-project.svg',
			},
		},
	};
};

export const CommunicationsTypeDict: Record<string, string> = Object.freeze({
	chat: 'chat',
	channel: 'channel',
	collab: 'collab',
});

export const getCommunicationsRecentTabOptions = function(
	entityType: string,
	chatType: CommunicationsTypeDict.chat | CommunicationsTypeDict.channel | CommunicationsTypeDict.collab,
): TabOptions {
	let title = '';
	let subtitle = '';

	if (chatType === CommunicationsTypeDict.chat)
	{
		title = Loc.getMessage('HUMANRESOURCES_STRUCTURE_COMPONENTS_CHAT_STUB_TITLE');
		subtitle = entityType === EntityTypes.team
			? Loc.getMessage('HUMANRESOURCES_STRUCTURE_COMPONENTS_CHAT_TEAM_STUB_SUBTITLE')
			: Loc.getMessage('HUMANRESOURCES_STRUCTURE_COMPONENTS_CHAT_DEPARTMENT_STUB_SUBTITLE')
		;
	}
	else if (chatType === CommunicationsTypeDict.channel)
	{
		title = Loc.getMessage('HUMANRESOURCES_STRUCTURE_COMPONENTS_CHANNEL_STUB_TITLE');
		subtitle = entityType === EntityTypes.team
			? Loc.getMessage('HUMANRESOURCES_STRUCTURE_COMPONENTS_CHANNEL_TEAM_STUB_SUBTITLE')
			: Loc.getMessage('HUMANRESOURCES_STRUCTURE_COMPONENTS_CHANNEL_DEPARTMENT_STUB_SUBTITLE')
		;
	}
	else
	{
		title = Loc.getMessage('HUMANRESOURCES_STRUCTURE_COMPONENTS_COLLAB_STUB_TITLE');
		subtitle = entityType === EntityTypes.team
			? Loc.getMessage('HUMANRESOURCES_STRUCTURE_COMPONENTS_COLLAB_TEAM_STUB_SUBTITLE')
			: Loc.getMessage('HUMANRESOURCES_STRUCTURE_COMPONENTS_COLLAB_DEPARTMENT_STUB_SUBTITLE')
		;
	}

	return {
		visible: false,
		stub: true,
		stubOptions: {
			title,
			subtitle,
		},
	};
};
