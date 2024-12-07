import { UI } from 'ui.notification';
import { ajax, Loc } from 'main.core';
import { MessageBox } from 'ui.dialogs.messagebox';

export class SignCancellation
{
	#isCancellationInProgress: boolean = false;
	cancelWithConfirm(documentUid: string): void
	{
		if (this.#isCancellationInProgress)
		{
			return;
		}

		const signingCancellationDialog = new MessageBox({
			title: Loc.getMessage('SIGN_CANCELLATION_ITEM_SIGNING_CANCEL_DIALOG_TITLE'),
			message: Loc.getMessage('SIGN_CANCELLATION_ITEM_SIGNING_CANCEL_DIALOG_TEXT'),
			modal: true,
		});

		const yesButton = new BX.UI.Button({
			text: Loc.getMessage('SIGN_CANCELLATION_ITEM_SIGNING_CANCEL_DIALOG_YES_BUTTON_TEXT'),
			color: BX.UI.Button.Color.DANGER,
			onclick: (button: BaseButton) => {
				if (this.#isCancellationInProgress === true)
				{
					return;
				}
				this.#isCancellationInProgress = true;
				button.setState(BX.UI.Button.State.WAITING);
				void this.cancelSigningProcess(documentUid).finally(() => {
					this.#isCancellationInProgress = false;
					signingCancellationDialog.close();
				});
			},
		});

		const noButton = new BX.UI.Button({
			text: Loc.getMessage('SIGN_CANCELLATION_ITEM_SIGNING_CANCEL_DIALOG_NO_BUTTON_TEXT'),
			color: BX.UI.Button.Color.LIGHT_BORDER,
			onclick: () => {
				signingCancellationDialog.close();
			},
		});

		signingCancellationDialog.setButtons([
			yesButton,
			noButton,
		]);

		signingCancellationDialog.show();
	}

	cancelSigningProcess(documentUid: string): Promise
	{
		return new Promise((resolve, reject) => {
			ajax.runAction(
				'sign.api_v1.document.signing.stop',
				{
					json: {
						uid: documentUid,
					},
					preparePost: false,
				},
			).then((response) => {
				UI.Notification.Center.notify({
					content: Loc.getMessage('SIGN_CANCELLATION_ITEM_SIGNING_CANCEL_SUCCESS'),
					autoHideDelay: 5000,
				});
				resolve(response);
			}, (response) => {
				response.errors.forEach((error) => {
					UI.Notification.Center.notify({
						content: error.message,
						autoHideDelay: 5000,
					});
				});
				reject(response.errors);
			}).catch(() => {
				reject();
			});
		});
	}
}
