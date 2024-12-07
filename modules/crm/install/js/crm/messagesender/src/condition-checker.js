import { ajax, Loc, Type } from 'main.core';

type OpenLineItems = {
	[key: string]: OpenLineItem;
}

type OpenLineItem = {
	name: string;
	selected: boolean;
	url: string;
}

type StringObject = {
	[key: string]: string;
};

/**
 * @namespace {BX.Crm.Sender}
 */

export const OpenLineCodes: Readonly<string, string> = Object.freeze({
	telegram: 'telegrambot',
	notifications: 'notifications',
	// maybe whatsapp, vk, facebook in the future
});

export const Types: Readonly<string, string> = Object.freeze({
	bitrix24: 'bitrix24',
	sms: 'sms_provider',
});

const defaultMessages = Object.freeze({
	agreement: Loc.getMessage('CRM_MESSAGESENDER_B24_AGREEMENT_NOTIFY'),
	connectSuccess: Loc.getMessage('CRM_MESSAGESENDER_B24_CONNECT_SUCCESS'),
	connectAccessDenied: Loc.getMessage('CRM_MESSAGESENDER_B24_CONNECT_ACCESS_DENIED'),
	consentAgreementValidationError: Loc.getMessage('CRM_MESSAGESENDER_B24_CONSENT_AGREEMENT_VALIDATION_ERROR'),
});

export class ConditionChecker
{
	openLineItems: ?OpenLineItems = null;
	openLineCode: string;
	senderType: string;
	messages: StringObject;

	/**
	 * @param {string} openLineCode
	 * @param {string} senderType
	 * @param {StringObject | null} messages
	 * @param {OpenLineItems | null} openLineItems
	 * @returns {Promise<number|null>}
	 */
	static async checkAndGetLine({ openLineCode, senderType, messages = null, openLineItems = null }): Promise<boolean>
	{
		const instance = new ConditionChecker({ openLineCode, senderType, messages })

		if (Type.isObjectLike(openLineItems))
		{
			instance.setOpenLineItems(openLineItems);
		}

		return instance.check();
	}

	static async checkIsApproved({ openLineCode, senderType }): Promise<boolean>
	{
		const instance = new ConditionChecker({ openLineCode, senderType })

		return instance.checkApproveConsent();
	}

	/**
	 * @param {string} openLineCode
	 * @param {string} senderType
	 * @param {StringObject | null} messages
	 */
	constructor({ openLineCode, senderType, messages = null })
	{
		this.openLineCode = openLineCode;
		this.senderType = senderType;

		if (!Type.isObjectLike(messages))
		{
			messages = {};
		}

		this.messages = { ...defaultMessages, ...messages };
	}

	setOpenLineItems(items: OpenLineItems): ConditionChecker
	{
		this.openLineItems = items;

		return this;
	}

	async check(): Promise<number | null>
	{
		const isSelected = await this.#isOpenLineItemSelected();

		if (isSelected)
		{
			const isApproved = await this.#checkConsentApproved();
			if (isApproved)
			{
				const lineId: number = await this.#getLineId();
				if (!lineId)
				{
					return this.#openConnectSidePanel();
				}

				return Promise.resolve(lineId);
			}

			this.#showNotify(this.messages.agreement);

			return Promise.resolve(null);
		}

		const canEditConnector = await this.#canEditConnector();
		if (canEditConnector)
		{
			return this.#openConnectSidePanel();
		}

		this.#showConnectAlertMessage();

		return Promise.resolve(null);
	}

	async checkApproveConsent(): Promise<boolean | null>
	{
		const isApproved = await this.#checkConsentApproved();
		if (isApproved)
		{
			return Promise.resolve(true);
		}

		return Promise.resolve(null);
	}

	async #isOpenLineItemSelected(): Promise<boolean>
	{
		const item = await this.#getOpenLineItem();

		if (!item)
		{
			throw new Error(`OpenLine item with code: ${this.openLineCode} not found`);
		}

		return item.selected;
	}

	async #fetchOpenLineItems(): Promise<OpenLineItems>
	{
		return new Promise((resolve, reject) => {
			ajax.runAction('crm.controller.integration.openlines.getItems')
				.then(({ status, data, errors }) => {
					if (status === 'success')
					{
						resolve(data);

						return;
					}

					reject(errors);
				})
				.catch((data) => reject(data))
			;
		});
	}

	async #getOpenLineItem(force: boolean = false): ?OpenLineItem
	{
		if (this.openLineItems === null || force)
		{
			this.openLineItems = await this.#fetchOpenLineItems();
		}

		return this.openLineItems[this.openLineCode];
	}

	async #checkConsentApproved(): Promise<boolean>
	{
		if (this.senderType !== Types.bitrix24)
		{
			return Promise.resolve(true);
		}

		return new Promise((resolve) => {
			ajax.runAction('notifications.consent.Agreement.get')
				.then(({ data }) => {
					if (!data || !data.html)
					{
						resolve(true);
					}
					else
					{
						BX.UI.Dialogs.MessageBox.show({
							modal: true,
							minWidth: 980,
							title: data.title,
							message: data.html,
							buttons: [
								new BX.UI.Button({
									className: 'ui-btn-round',
									color: BX.UI.Button.Color.SUCCESS,
									text: Loc.getMessage('CRM_MESSAGESENDER_B24_CONSENT_ACCEPT'),
									onclick: (button) => {
										void ajax.runAction('notifications.consent.Agreement.approve');
										this.#showNotify(Loc.getMessage('CRM_MESSAGESENDER_B24_AGREEMENT_ACCEPT'));

										button.context.close();
										resolve(true);
									},
								}),
								new BX.UI.Button({
									className: 'ui-btn-round',
									color: BX.UI.Button.Color.LIGHT_BORDER,
									text: Loc.getMessage('CRM_MESSAGESENDER_B24_CONSENT_REJECT'),
									onclick: (button) => {
										button.context.close();
										resolve(false);
									},
								}),
							],
							popupOptions: {
								className: 'crm-agreement-terms-popup',
							},
						});
					}
				})
				.catch(() => {
					this.#showNotify(this.messages.consentAgreementValidationError);
					resolve(false);
				})
			;
		});
	}

	async #getLineId(): Promise<number | null>
	{
		return new Promise((resolve) => {
			const ajaxParameters = {
				connectorId: this.openLineCode,
				withConnector: true,
			};

			ajax.runAction('imconnector.Openlines.list', { data: ajaxParameters })
				.then(({ data }) => {
					if (Type.isArrayFilled(data))
					{
						const { lineId } = data[data.length - 1];
						resolve(lineId);

						return;
					}

					resolve(null);
				})
				.catch(() => this.#showNotify(Loc.getMessage('CRM_MESSAGESENDER_B24_OPENLINE_LINEID_ERROR')))
			;
		});
	}

	async #canEditConnector(): Promise<boolean>
	{
		return new Promise((resolve) => {
			ajax.runAction('imconnector.Openlines.hasAccess')
				.then(({ data }) => {
					if (data.canEditConnector)
					{
						resolve(true);

						return;
					}

					resolve(false);
				})
				.catch(() => this.#showConnectAlertMessage())
			;
		});
	}

	#showNotify(content: string): void
	{
		BX.UI.Notification.Center.notify({ content });
	}

	async #openConnectSidePanel(): Promise<boolean>
	{
		const item = await this.#getOpenLineItem();

		return new Promise((resolve) => {
			if (Type.isStringFilled(item.url))
			{
				BX.SidePanel.Instance.open(
					item.url,
					{
						width: 700,
						cacheable: false,
						events: {
							onClose: () => this.#onConnect(resolve),
						},
					},
				);

				return;
			}

			this.#showNotify(Loc.getMessage('CRM_MESSAGESENDER_B24_OPENLINE_LINEID_ERROR'));
			resolve(null);
		});
	}

	async #onConnect(resolve): Promise<number | null>
	{
		const lineId = await this.#getLineId();
		if (!lineId)
		{
			resolve(null);
			this.#showNotify(this.messages.agreement);

			return;
		}

		const item = await this.#getOpenLineItem(true);
		this.#showNotify(this.messages.connectSuccess.replace('#LINE_NAME#', item.name));

		const isApproved = await this.#checkConsentApproved();
		if (isApproved)
		{
			resolve(lineId);
		}
		else
		{
			this.#showNotify(this.messages.agreement);
			resolve(null);
		}
	}

	async #showConnectAlertMessage(): void
	{
		const item = await this.#getOpenLineItem();

		const message = this.messages.connectAccessDenied.replace('#SERVICE_NAME#', item.name);
		BX.UI.Dialogs.MessageBox.alert(message);
	}
}