import { Loc } from 'main.core';
import { Social } from 'ui.icon-set.api.core';

import { ChatService } from './types';

const ServicesConfig: ReadonlyMap<string, ChatService> = new Map([
	[
		'telegrambot',
		{
			id: 'telegrambot',
			available: true,
			connectLabel: Loc.getMessage('CRM_TIMELINE_GOTOCHAT_CONNECT_TELEGRAM'),
			inviteLabel: Loc.getMessage('CRM_TIMELINE_GOTOCHAT_INVITE_TELEGRAM'),
			title: Loc.getMessage('CRM_TIMELINE_GOTOCHAT_SERVICE_TELEGRAM'),
			commonClass: '--telegram',
			iconClass: Social.TELEGRAM_IN_CIRCLE,
			iconColor: '#2FC6F6',
		},
	],
	[
		'whatsappbyedna',
		{
			id: 'whatsappbyedna',
			available: false,
			connectLabel: Loc.getMessage('CRM_TIMELINE_GOTOCHAT_CONNECT_WHATSAPP'),
			inviteLabel: Loc.getMessage('CRM_TIMELINE_GOTOCHAT_INVITE_WHATSAPP'),
			soonLabel: Loc.getMessage('CRM_TIMELINE_GOTOCHAT_SOON_WHATSAPP'),
			title: Loc.getMessage('CRM_TIMELINE_GOTOCHAT_SERVICE_WHATSAPP'),
			commonClass: '--whatsapp',
			iconClass: Social.WHATSAPP,
		},
	],
	[
		'vkgroup',
		{
			id: 'vkgroup',
			available: false,
			connectLabel: Loc.getMessage('CRM_TIMELINE_GOTOCHAT_CONNECT_VK'),
			inviteLabel: Loc.getMessage('CRM_TIMELINE_GOTOCHAT_INVITE_VK'),
			soonLabel: Loc.getMessage('CRM_TIMELINE_GOTOCHAT_SOON_VK'),
			title: Loc.getMessage('CRM_TIMELINE_GOTOCHAT_SERVICE_VK'),
			region: 'ru',
			commonClass: '--vk',
			iconClass: Social.VK,
		},
	],
	[
		'facebook',
		{
			id: 'facebook',
			available: false,
			connectLabel: Loc.getMessage('CRM_TIMELINE_GOTOCHAT_CONNECT_FACEBOOK'),
			inviteLabel: Loc.getMessage('CRM_TIMELINE_GOTOCHAT_INVITE_FACEBOOK'),
			soonLabel: Loc.getMessage('CRM_TIMELINE_GOTOCHAT_SOON_FACEBOOK'),
			title: Loc.getMessage('CRM_TIMELINE_GOTOCHAT_SERVICE_FACEBOOK'),
			region: '!ru',
			commonClass: '--facebook',
			iconClass: Social.FACEBOOK,
		},
	],
]);

export default ServicesConfig;
