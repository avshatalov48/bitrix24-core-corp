import { Router } from 'crm.router';
import { ajax, Loc, Type } from 'main.core';
import type { SenderType } from '../common/consent-approver';
import { showNotify } from '../common/utilites';
import type { Scenario } from '../scenario';

type OpenLineItems = {
	[key: string]: OpenLineItem;
}

type OpenLineItem = {
	name: string;
	selected: boolean;
	url: string;
}

export type Settings = {
	marketUrl: string;
	canUseNotifications: boolean;
}

export class Base implements Scenario
{
	openLineItems: OpenLineItems = null;
	senderType: SenderType = null;
	entityTypeId: number = null;

	constructor(params: Object)
	{
		if (Type.isPlainObject(params?.openLineItems))
		{
			this.openLineItems = params.openLineItems ?? null;
			this.senderType = params.senderType ?? null;
		}

		if (Type.isNumber(params?.entityTypeId))
		{
			this.entityTypeId = params.entityTypeId;
		}
	}

	getOpenLineCode(): string
	{
		throw new Error('Must be implement in child class');
	}

	async checkAndGetLineId(): Promise<number | null>
	{
		await this.#showConnectAlertMessage();

		return Promise.resolve(null);
	}

	async isOpenLineItemSelected(force: boolean = false): Promise<boolean>
	{
		const item = await this.getOpenLineItem(force);

		if (!item)
		{
			throw new ReferenceError(`OpenLine item with code: ${this.getOpenLineCode()} not found`);
		}

		return item.selected;
	}

	async getOpenLineItem(force: boolean = false, openLineCode: string = null): ?OpenLineItem
	{
		if (this.openLineItems === null || force)
		{
			this.openLineItems = await this.fetchOpenLineItems();
		}

		return this.openLineItems[openLineCode ?? this.getOpenLineCode()];
	}

	async fetchOpenLineItems(): Promise<OpenLineItems>
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
				.catch((data) => {
					reject(data);
				})
			;
		});
	}

	async getLineId(): Promise<number | null>
	{
		return new Promise((resolve) => {
			const ajaxParameters = {
				connectorId: this.getOpenLineCode(),
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
				.catch(() => {
					showNotify(Loc.getMessage('CRM_MESSAGESENDER_B24_OPENLINE_LINEID_ERROR'));
				})
			;
		});
	}

	async canEditConnector(): Promise<boolean>
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

	async #showConnectAlertMessage(): Promise<void>
	{
		const item = await this.getOpenLineItem();

		const message = Loc.getMessage(
			'CRM_MESSAGESENDER_B24_CONNECT_ACCESS_DENIED',
			{
				'#SERVICE_NAME#': item.name,
			},
		);

		showNotify(message);
	}

	async openConnectSidePanel(url: string, onCloseCallback: Function): Promise
	{
		return new Promise((resolve) => {
			if (Type.isStringFilled(url))
			{
				void Router.openSlider(
					url,
					{
						width: 700,
						cacheable: false,
					},
				).then(() => {
					onCloseCallback(resolve);
				});

				return;
			}

			showNotify(Loc.getMessage('CRM_MESSAGESENDER_B24_OPENLINE_LINEID_ERROR'));
			resolve(null);
		});
	}
}
