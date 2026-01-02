import {
	ChatHeaderIntegrationConfig,
	MessageIntegrationConfig,
	SidebarHeaderIntegrationConfig
} from '../../../../api/dialog-opener/dialog-opener';

export type DialogConfig = {
	header: ChatHeaderIntegrationConfig,
	sidebar: SidebarHeaderIntegrationConfig,
	message: MessageIntegrationConfig,
};