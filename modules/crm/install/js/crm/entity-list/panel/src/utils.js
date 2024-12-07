import { Loc } from 'main.core';
import { UI } from 'ui.notification';

export const NOTIFICATION_AUTO_HIDE_DELAY = 5000;

export function showAnotherProcessRunningNotification(): void
{
	UI.Notification.Center.notify({
		content: Loc.getMessage('CRM_ENTITY_LIST_PANEL_ANOTHER_PROCESS_IN_PROGRESS'),
		autoHide: true,
		autoHideDelay: NOTIFICATION_AUTO_HIDE_DELAY,
	});
}
