import { ajax, Extension, Loc, Reflection, Type } from 'main.core';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';
import { showNotify } from '../common/utilites';
import type { Settings } from './base';
import { Base } from './base';

export class WhatsApp extends Base
{
	async checkAndGetLineId(): Promise<number | null>
	{
		const isWhatsAppAvailable = await this.#checkVirtualWhatsAppAvailable();
		if (!isWhatsAppAvailable)
		{
			return null;
		}

		if (await this.#isVirtualWhatsAppConnected())
		{
			const hasAvailableProvider = await this.#hasAvailableSmsProvider();

			if (hasAvailableProvider)
			{
				// notification connector does not take into account the open line number when generating the link
				return Promise.resolve(0);
			}

			const canEditConnector = await this.canEditConnector();
			if (canEditConnector)
			{
				return this.#showMarketplaceDialog();
			}

			return super.checkAndGetLineId();
		}

		const canEditConnector = await this.canEditConnector();
		if (canEditConnector)
		{
			const url = this.openLineItems?.virtual_whatsapp?.url;

			return this.openConnectSidePanel(url, this.onConnectVirtualWhatsApp.bind(this));
		}

		return super.checkAndGetLineId();
	}

	async #checkVirtualWhatsAppAvailable(): Promise<boolean>
	{
		const config = await this.#fetchVirtualWhatsAppConfig();

		if (Type.isStringFilled(config.infoHelperCode))
		{
			if (Reflection.getClass('BX.UI.InfoHelper.show'))
			{
				BX.UI.InfoHelper.show(config.infoHelperCode);
			}

			return false;
		}

		return true;
	}

	#fetchVirtualWhatsAppConfig(): Promise<Object>
	{
		const { entityTypeId } = this;

		return new Promise((resolve, reject) => {
			ajax
				.runAction(
					'crm.controller.messagesender.conditionchecker.getVirtualWhatsAppConfig',
					{
						data: {
							entityTypeId,
						},
					},
				)
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

	async onConnectVirtualWhatsApp(resolve: Function): Promise<number | null>
	{
		if (await this.#isVirtualWhatsAppConnected())
		{
			return resolve(this.checkAndGetLineId());
		}

		showNotify(Loc.getMessage('CRM_MESSAGESENDER_B24_OPENLINE_LINEID_ERROR'));

		return resolve(null);
	}

	async #isVirtualWhatsAppConnected(): Promise<boolean>
	{
		const virtualWhatsAppItem = await this.getOpenLineItem(true, 'virtual_whatsapp');

		return virtualWhatsAppItem?.selected;
	}

	getOpenLineCode(): string
	{
		return 'notifications';
	}

	async #hasAvailableSmsProvider(): Promise<boolean>
	{
		const smsSenders = await this.#getSmsSenders();

		return Promise.resolve(smsSenders.some((provider) => provider.canUse && !provider.isTemplatesBased));
	}

	async #getSmsSenders(): Promise<Object[]>
	{
		const { entityTypeId } = this;

		return new Promise((resolve, reject) => {
			ajax
				.runAction(
					'crm.controller.messagesender.conditionchecker.getSmsSenders',
					{
						data: {
							entityTypeId,
						},
					},
				)
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

	#showMarketplaceDialog(): Promise<number | null>
	{
		return new Promise((resolve) => {
			MessageBox.show({
				message: Loc.getMessage('CRM_MESSAGESENDER_B24_CONDITION_CHECKER_MARKET_MESSAGE'),
				modal: true,
				buttons: MessageBoxButtons.OK_CANCEL,
				okCaption: Loc.getMessage('CRM_MESSAGESENDER_B24_CONDITION_CHECKER_OK_BTN_TEXT'),
				onOk: (messageBox) => {
					void this.#openMarketplace(resolve);
					messageBox.close();
				},
			});
		});
	}

	#openMarketplace(resolve): Promise
	{
		const marketUrl = this.#getSettings().marketUrl;

		BX.SidePanel.Instance.open(
			marketUrl,
			{
				cacheable: false,
				events: {
					onClose: () => {
						void this.#onCloseMarketplace(resolve);
					},
				},
			},
		);
	}

	async #onCloseMarketplace(resolve): Promise<void>
	{
		const hasAvailableSmsProvider = await this.#hasAvailableSmsProvider();

		if (hasAvailableSmsProvider)
		{
			resolve(0);

			return;
		}

		showNotify(Loc.getMessage('CRM_MESSAGESENDER_B24_OPENLINE_LINEID_ERROR'));

		resolve(null);
	}

	#getSettings(): Settings
	{
		return Extension.getSettings('crm.messagesender');
	}
}
