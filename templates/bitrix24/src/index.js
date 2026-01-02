import { Reflection } from 'main.core';
import { PartnerForm } from './js/partner-form/partner-form';
import { SiteTemplate } from './js/site-template';
import { SearchTitle } from './js/header/search-title';
import { AvatarButton } from './js/header/avatar-button';
import { LicenseButton } from './js/header/license-button';
import { InvitationButton } from './js/header/invitation-button';

import { languageSwitcher } from './js/language-switcher';
import { showPartnerConnectForm } from './js/partner-form/show-partner-connect-form';

import './css/index';

const Template = new SiteTemplate();
export {
	languageSwitcher,
	PartnerForm,
	Template,
	SearchTitle,
	InvitationButton,
	LicenseButton,
	AvatarButton,
};

// Compatibility
/**
 * @deprecated
 */
window.showPartnerForm = showPartnerConnectForm;

/**
 * @deprecated
 */
window.B24 = {
	/**
	 * @deprecated
	 */
	licenseInfoPopup: {
		show(popupId, title, content, showDemoButton)
		{
			const LicenseInfoPopup = Reflection.getClass('BX.Bitrix24.LicenseInfoPopup');
			if (LicenseInfoPopup)
			{
				LicenseInfoPopup.show(popupId, title, content, showDemoButton);
			}
		},
	},

	/**
	 * @deprecated
	 */
	updateCounters(counters, send)
	{
		const LeftMenu = Reflection.getClass('BX.Intranet.LeftMenu');
		if (LeftMenu)
		{
			LeftMenu.updateCounters(counters, send);
		}
	},
};
