import { Event, Type } from 'main.core';
import { sendData } from 'ui.analytics';

export class Analytics
{
	static TOOLS = 'Invitation';
	static CATEGORY_INVITATION = 'invitation';
	static EVENT_COPY = 'copy_invitation_link';
	static ADMIN_ALLOW_MODE_Y = 'askAdminToAllow_Y';
	static ADMIN_ALLOW_MODE_N = 'askAdminToAllow_N';
	static IS_ADMIN_Y = 'isAdmin_Y';
	static IS_ADMIN_N = 'isAdmin_N';

	#cSection: Object;
	#isAdmin: boolean;

	constructor(cSection: Object, isAdmin: boolean)
	{
		this.#cSection = cSection;
		this.#isAdmin = isAdmin;
	}

	#getAdminAllowMode(): string
	{
		return document.querySelector('#allow_register_confirm').checked
			? Analytics.ADMIN_ALLOW_MODE_Y
			: Analytics.ADMIN_ALLOW_MODE_N;
	}

	#getIsAdmin(): string
	{
		return this.#isAdmin ? Analytics.IS_ADMIN_Y : Analytics.IS_ADMIN_N;
	}

	#getCSection(): string
	{
		return this.#cSection.source;
	}

	send(): void
	{
		sendData({
			tool: Analytics.TOOLS,
			category: Analytics.CATEGORY_INVITATION,
			event: Analytics.EVENT_COPY,
			c_section: this.#getCSection(),
			p1: this.#getIsAdmin(),
			p2: this.#getAdminAllowMode(),
		});
	}
}