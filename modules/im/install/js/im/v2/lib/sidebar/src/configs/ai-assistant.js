import { Core } from 'im.v2.application.core';
import { SidebarMainPanelBlock } from 'im.v2.const';

import { SidebarConfig } from '../classes/config';

import type { ImModelChat } from 'im.v2.model';

const isAiAssistant = (chatContext: ImModelChat) => Core.getStore().getters['users/bots/isAiAssistant'](chatContext.dialogId);

const aiAssistantConfig = new SidebarConfig({
	blocks: [
		SidebarMainPanelBlock.user,
		SidebarMainPanelBlock.tariffLimit,
		SidebarMainPanelBlock.info,
	],
});

export { isAiAssistant, aiAssistantConfig };
