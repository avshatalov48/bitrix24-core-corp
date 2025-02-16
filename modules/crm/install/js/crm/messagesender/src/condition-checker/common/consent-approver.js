import { ajax, Loc } from 'main.core';

export const Types: Readonly<string, string> = Object.freeze({
	bitrix24: 'bitrix24',
	sms: 'sms_provider',
});

export type SenderType = Types.sms | Types.bitrix24;

export class ConsentApprover
{
	#senderType: ?string = null;

	constructor(senderType: ?string = null)
	{
		this.#senderType = senderType;
	}

	async checkAndApprove(): Promise<boolean>
	{
		if (this.#senderType !== Types.bitrix24)
		{
			return Promise.resolve(true);
		}

		return new Promise((resolve) => {
			ajax.runAction('notifications.consent.Agreement.get')
				.then(({ data }) => {
					if (!data || !data.html)
					{
						resolve(true);

						return;
					}

					this.#showConsentAgreementBox(data, resolve);
				})
				.catch(() => {
					this.#showErrorNotify();

					resolve(false);
				})
			;
		});
	}

	#showConsentAgreementBox({ title, html: message }, resolve: Function): void
	{
		BX.UI.Dialogs.MessageBox.show({
			modal: true,
			minWidth: 980,
			title,
			message,
			buttons: this.#getButtons(resolve),
			popupOptions: {
				className: 'crm-agreement-terms-popup',
			},
		});
	}

	#getButtons(resolve: Function): BX.UI.Button[]
	{
		return [
			new BX.UI.Button({
				className: 'ui-btn-round',
				color: BX.UI.Button.Color.SUCCESS,
				text: Loc.getMessage('CRM_MESSAGESENDER_B24_CONSENT_ACCEPT'),
				onclick: (button) => {
					this
						.#approveConsent()
						.then((isApprovedConsent) => {
							if (isApprovedConsent)
							{
								this.#showNotify(Loc.getMessage('CRM_MESSAGESENDER_B24_AGREEMENT_ACCEPT'));
							}

							this.#closeAgreementBox(button);
							resolve(true);
						})
						.catch(() => {
							this.#showErrorNotify();
							resolve(false);
						})
					;
				},
			}),
			new BX.UI.Button({
				className: 'ui-btn-round',
				color: BX.UI.Button.Color.LIGHT_BORDER,
				text: Loc.getMessage('CRM_MESSAGESENDER_B24_CONSENT_REJECT'),
				onclick: (button) => {
					this.#closeAgreementBox(button);

					resolve(false);
				},
			}),
		];
	}

	#approveConsent(): Promise<boolean>
	{
		return new Promise((resolve) => {
			ajax
				.runAction('notifications.consent.Agreement.approve')
				.then((response) => {
					if (response?.status === 'success' && response?.data)
					{
						resolve(true);

						return;
					}

					resolve(false);
				})
				.catch(() => {
					resolve(false);
				})
			;
		});
	}

	#closeAgreementBox({ context }): void
	{
		context.close();
	}

	#showErrorNotify(): void
	{
		this.#showNotify(Loc.getMessage('CRM_MESSAGESENDER_B24_CONSENT_AGREEMENT_VALIDATION_ERROR'));
	}

	#showNotify(content: string): void
	{
		BX.UI.Notification.Center.notify({ content });
	}
}
