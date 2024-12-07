import { Loc, Text } from 'main.core';
import { Button } from 'ui.buttons';
import { MessageBox } from 'ui.dialogs.messagebox';

type Props = {
	message?: string,
	onAccept?: () => void,
	onDecline?: () => void,
	onSuccess?: () => void,
	onError?: () => void,
}

export class ApacheSupersetCleanPopup
{
	#onAcceptHandler = () => {};
	#onDeclineHandler = () => {};

	#onSuccessHandler = () => {};
	#onErrorHandler = () => {};

	#message: string;

	constructor(props: Props = {})
	{
		this.#message = props.message || Loc.getMessage('SUPERSET_CLEANER_DELETE_POPUP_TEXT_MSGVER_2');
		this.#onAcceptHandler = props.onAccept || this.#onAcceptHandler;
		this.#onDeclineHandler = props.onDecline || this.#onDeclineHandler;
		this.#onSuccessHandler = props.onSuccess || this.#onSuccessHandler;
		this.#onErrorHandler = props.onError || this.#onErrorHandler;
	}

	show()
	{
		const popup = new MessageBox({
			title: Loc.getMessage('SUPERSET_CLEANER_DELETE_POPUP_TITLE'),
			message: this.#message,
			buttons: [
				new Button({
					color: Button.Color.PRIMARY,
					text: Loc.getMessage('SUPERSET_CLEANER_DELETE_POPUP_BUTTON_NO'),
					events: {
						click: () => {
							this.#onDeclineHandler();
							popup.close();
						},
					},
				}),
				new Button({
					color: Button.Color.LIGHT_BORDER,
					text: Loc.getMessage('SUPERSET_CLEANER_DELETE_POPUP_BUTTON_YES'),
					events: {
						click: () => {
							this.#onAcceptHandler();
							this.#deleteInstance();
							popup.close();
						},
					},
				}),
			],
			popupOptions: {
				autoHide: true,
			},
		});

		popup.show();
	}

	#deleteInstance()
	{
		BX.ajax.runAction('biconnector.superset.clean')
			.then(() => {
				BX.UI.Notification.Center.notify({
					content: Loc.getMessage('SUPERSET_CLEANER_DELETE_DONE'),
				});

				this.#onSuccessHandler();
			})
			.catch((response) => {
				BX.UI.Notification.Center.notify({
					content: Text.encode(response.errors[0].message),
				});

				this.#onErrorHandler();
			});
	}
}
