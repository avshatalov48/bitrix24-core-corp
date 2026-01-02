import { Loc } from 'main.core';
import { MessageBox } from 'ui.dialogs.messagebox';

export default class Notifier
{
	message: String;

	constructor(
		message: String,
	)
	{
		this.message = message;
	}

	getTitle(): String
	{
		return Loc.getMessage('BAAS_ERROR_REGISTRATION_TITLE');
	}

	getMessage(): String
	{
		return this.message;
	}

	show(): void
	{
		MessageBox.show({
			title: this.getTitle(),
			message: this.getMessage(),
			modal: true,
			buttons: BX.UI.Dialogs.MessageBoxButtons.OK,
			maxWidth: 1000,
		});
	}
}
