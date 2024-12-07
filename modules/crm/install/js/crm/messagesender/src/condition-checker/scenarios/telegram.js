import { Loc } from 'main.core';
import { ConsentApprover } from '../common/consent-approver';
import { showNotify } from '../common/utilites';
import { Base } from './base';

export class Telegram extends Base
{
	async checkAndGetLineId(): Promise<number | null>
	{
		const isSelected = await this.isOpenLineItemSelected();

		if (isSelected)
		{
			const isApproved = await this.#checkConsentApproved();
			if (isApproved)
			{
				const lineId: number = await this.getLineId();
				if (!lineId)
				{
					const item = await this.getOpenLineItem();

					return this.openConnectSidePanel(item.url, this.onConnect.bind(this));
				}

				return Promise.resolve(lineId);
			}

			showNotify(Loc.getMessage('CRM_MESSAGESENDER_B24_AGREEMENT_NOTIFY'));

			return Promise.resolve(null);
		}

		const canEditConnector = await this.canEditConnector();
		if (canEditConnector)
		{
			const item = await this.getOpenLineItem();

			return this.openConnectSidePanel(item.url, this.onConnect.bind(this));
		}

		return super.checkAndGetLineId();
	}

	async onConnect(resolve: Function): Promise<number | null>
	{
		const lineId = await this.getLineId();

		if (lineId === null)
		{
			showNotify(Loc.getMessage('CRM_MESSAGESENDER_B24_OPENLINE_LINEID_ERROR'));

			return resolve(null);
		}

		const item = await this.getOpenLineItem(true);
		showNotify(Loc.getMessage('CRM_MESSAGESENDER_B24_CONNECT_SUCCESS', {
			'#LINE_NAME#': item.name,
		}));

		const isApproved = await this.#checkConsentApproved();
		if (isApproved)
		{
			return resolve(lineId);
		}

		showNotify(Loc.getMessage('CRM_MESSAGESENDER_B24_AGREEMENT_NOTIFY'));

		return resolve(null);
	}

	async #checkConsentApproved(): Promise<boolean>
	{
		return (new ConsentApprover(this.senderType)).checkAndApprove();
	}

	getOpenLineCode(): string
	{
		return 'telegrambot';
	}
}
