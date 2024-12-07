import { sendData } from 'ui.analytics';

export class Analytics
{
	static TOOLS = 'headerPopup';
	static TOOLS_LEGACY = 'Invitation';
	static CATEGORY_INVITATION = 'invitation';
	static CATEGORY_INVITATION_LEGACY = 'invitation';
	static EVENT_NAME_LEGACY = 'drawer_open';
	static SECTION_POPUP = 'headerPopup';
	static EVENT_SHOW = 'show';
	static EVENT_OPEN_SLIDER_INVITATION = 'drawer_open';
	static EVENT_OPEN_STRUCTURE = 'vis_structure_open';
	static EVENT_OPEN_USER_LIST = 'company_open';
	static EVENT_OPEN_SLIDER_EXTRANET_INVITATION = 'extranetinvitation_open';
	static isAdmin: boolean = false;

	static send(event: string): void
	{
		sendData({
			tool: Analytics.TOOLS,
			category: Analytics.CATEGORY_INVITATION,
			event: event,
			p1: Analytics.isAdmin ? 'isAdmin_Y' : 'isAdmin_N',
		});
	}

	sendLegacy(section: string)
	{
		sendData({
			tool: Analytics.TOOLS_LEGACY,
			category: Analytics.CATEGORY_INVITATION_LEGACY,
			event: Analytics.EVENT_NAME_LEGACY,
			c_section: section,
			p1: Analytics.isAdmin ? 'isAdmin_Y' : 'isAdmin_N',
		});
	}
}
