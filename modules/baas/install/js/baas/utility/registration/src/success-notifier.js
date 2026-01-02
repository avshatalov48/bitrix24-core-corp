import { Loc } from 'main.core';
import { MessageBox } from 'ui.dialogs.messagebox';

export default class SuccessNotifier
{
	show(): void
	{
		MessageBox.show({
			title: Loc.getMessage('BAAS_SUCCESS_REGISTRATION_TITLE'),
			message: Loc.getMessage('BAAS_SUCCESS_REGISTRATION_MESSAGE', { '#LINK#': '/bitrix/admin/baas_marketplace.php' }),
			modal: true,
			buttons: BX.UI.Dialogs.MessageBoxButtons.OK,
			maxWidth: 1000,
		});
	}
}
