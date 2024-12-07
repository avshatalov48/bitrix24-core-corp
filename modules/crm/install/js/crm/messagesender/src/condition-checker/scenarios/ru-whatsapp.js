import { ajax, Loc, Reflection, Type } from 'main.core';
import { showNotify } from '../common/utilites';
import { Base } from './base';

export class RuWhatsApp extends Base
{
	async checkAndGetLineId(): Promise<number | null>
	{
		const isWhatsAppAvailable = await this.#checkVirtualWhatsAppAvailable();
		if (!isWhatsAppAvailable)
		{
			return null;
		}

		const isSelected = await this.isOpenLineItemSelected();

		if (isSelected)
		{
			if (await this.#isVirtualWhatsAppConnected())
			{
				return this.getLineId();
			}

			const canEditConnector = await this.canEditConnector();
			if (canEditConnector)
			{
				const url = this.openLineItems?.virtual_whatsapp?.url;

				return this.openConnectSidePanel(url, this.onConnectVirtualWhatsApp.bind(this));
			}

			return super.checkAndGetLineId();
		}

		const canEditConnector = await this.canEditConnector();
		if (canEditConnector)
		{
			const item = await this.getOpenLineItem();

			return this.openConnectSidePanel(item.url, this.onConnect.bind(this));
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
			return resolve(this.getLineId());
		}

		showNotify(Loc.getMessage('CRM_MESSAGESENDER_B24_OPENLINE_LINEID_ERROR'));

		return resolve(null);
	}

	async #isVirtualWhatsAppConnected(): Promise<boolean>
	{
		const virtualWhatsAppItem = await this.getOpenLineItem(true, 'virtual_whatsapp');

		return virtualWhatsAppItem?.selected;
	}

	async onConnect(resolve: Function): Promise<number | null>
	{
		const isSelected = await this.isOpenLineItemSelected(true);

		if (isSelected)
		{
			return resolve(this.checkAndGetLineId());
		}

		showNotify(Loc.getMessage('CRM_MESSAGESENDER_B24_OPENLINE_LINEID_ERROR'));

		return resolve(null);
	}

	getOpenLineCode(): string
	{
		return 'notifications';
	}
}
