import { EmailButton } from './communications/email-button';
import { MessengerButton } from './communications/messenger-button';

import { PhoneButton } from './communications/phone-button';
import NavigationBar from './navigationbar';
import ToolbarComponent from './toolbar-component';

/**
 * @memberOf BX.Crm.ToolbarComponent.Communications
 */
ToolbarComponent.Communications = Object.freeze({
	PhoneButton,
	EmailButton,
	MessengerButton,
});

/**
 * @memberOf BX.Crm
 */
export {
	ToolbarComponent,
	NavigationBar,
};
