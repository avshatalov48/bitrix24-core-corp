import { UI } from 'ui.notification';
import { Text, Loc } from 'main.core';

const SUCCESS_STATUS = 'success';

export class ErrorHandler
{
	handleAttachError(response: Object): void
	{
		if (response?.status === SUCCESS_STATUS)
		{
			return;
		}

		const error = response?.errors?.[0] ?? null;
		if (error === null)
		{
			return;
		}

		const errorMessage = Loc.getMessage('CRM_COPILOT_CALL_CARD_REPLACEMENT_COMMON_ATTACH_ERROR');
		this.displayErrorNotification(errorMessage);

		console.error(error);
	}

	displayErrorNotification(errorMessage: string, autoHideDelay: number = 6000): void
	{
		UI.Notification.Center.notify({
			content: Text.encode(errorMessage),
			autoHideDelay,
		});
	}
}
