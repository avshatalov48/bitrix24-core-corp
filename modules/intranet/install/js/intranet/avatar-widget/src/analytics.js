import { sendData } from 'ui.analytics';

export class Analytics
{
	static TOOLS = 'intranet';
	static CATEGORY = 'ava_menu';
	static CATEGORY_PROFILE = 'user_profile';
	static EVENT_OPEN_WIDGET = 'menu_open';
	static EVENT_PROFILE_VIEW = 'profile_view';
	static EVENT_CLICK_SALARY = 'click_salary';
	static EVENT_CLICK_INSTALL_DESKTOP_APP = 'click_install_desktop_app';
	static EVENT_CLICK_INSTALL_MOBILE_APP = 'click_install_mobile_app';
	static EVENT_CLICK_FAST_MOBILE_AUTH = 'click_fast_mobile_auth';
	static EVENT_CLICK_2FA_SETUP = 'click_2fa_setup';
	static EVENT_CLICK_EXTENSION = 'click_extension';
	static EVENT_CLICK_LOGOUT = 'click_logout';
	static EVENT_CLICK_CHANGE_PORTAL_THEME = 'click_change_portal_theme';
	static EVENT_CLICK_NETWORK = 'click_network';
	static EVENT_CLICK_ACTIVITY_PORTAL_LIST = 'click_activity_portal_list';
	static EVENT_CLICK_PULSE = 'click_open_pulse';
	static EVENT_CLICK_MY_DOCUMENTS = 'click_open_my_documents';

	static send(event: string): void
	{
		sendData({
			tool: Analytics.TOOLS,
			category: Analytics.CATEGORY,
			event,
		});
	}

	static sendOpenProfile(): void
	{
		sendData({
			tool: Analytics.TOOLS,
			category: Analytics.CATEGORY_PROFILE,
			event: Analytics.EVENT_PROFILE_VIEW,
			c_section: Analytics.CATEGORY,
		});
	}

	static sendOpenCommonSecurity(): void
	{
		sendData({
			tool: 'settings',
			category: 'common_security',
			event: 'start_page',
			c_section: Analytics.CATEGORY,
		});
	}
}
