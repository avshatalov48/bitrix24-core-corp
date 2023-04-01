import {Base} from './base';
import ConfigurableItem from '../configurable-item';
import {PULL} from 'pull.client';
import {ajax, Type} from 'main.core';

export class Delivery extends Base
{
	#needCheckRequestStatus: boolean = null;
	#checkRequestStatusTimeout: number = null;
	#isPullSubscribed: boolean = false;

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return item.getType() === 'Activity:Delivery';
	}

	onInitialize(item: ConfigurableItem): void
	{
		this.#updateCheckRequestStatus(item);
		this.#subscribePullEvents(item);
	}

	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const {action, actionType, actionData, animationCallbacks} = actionParams;

		if (actionType !== 'jsEvent')
		{
			return;
		}

		if (action === 'Delivery:MakeCall' && actionData)
		{
			this.#makeCall(actionData);
		}
	}

	onAfterItemRefreshLayout(item: ConfigurableItem): void
	{
		this.#updateCheckRequestStatus(item);
	}

	#makeCall(actionData): void
	{
		if (
			!Type.isStringFilled(actionData.phoneNumber)
			|| !Type.isBoolean(actionData.canUserPerformCalls)
		)
		{
			return;
		}

		if (
			!Type.isUndefined(window.top['BXIM'])
			&& actionData.canUserPerformCalls !== false
		)
		{
			window.top['BXIM'].phoneTo(actionData.phoneNumber);
		}
		else
		{
			window.open('tel:' + actionData.phoneNumber, '_self');
		}
	}

	#subscribePullEvents(item: ConfigurableItem): void
	{
		if (this.#isPullSubscribed)
		{
			return;
		}

		this.#subscribeShipmentEvents(item);
		this.#subscribeDeliveryServiceEvents(item);
		this.#subscribeDeliveryRequestEvents(item);

		this.#isPullSubscribed = true;
	}

	#subscribeShipmentEvents(item: ConfigurableItem): void
	{
		const shipmentIds = this.#getShipmentIds(item);

		PULL.subscribe({
			moduleId: 'crm',
			command: 'onOrderShipmentSave',
			callback: (params) => {
				if (shipmentIds.some(id => id == params.FIELDS.ID))
				{
					item.reloadFromServer();
				}
			}
		});

		PULL.extendWatch('CRM_ENTITY_ORDER_SHIPMENT');
	}

	#subscribeDeliveryServiceEvents(item: ConfigurableItem): void
	{
		const deliveryServiceIds = this.#getDeliveryServiceIds(item);

		PULL.subscribe({
			moduleId: 'sale',
			command: 'onDeliveryServiceSave',
			callback: (params) => {
				if (deliveryServiceIds.some(id => id == params.ID))
				{
					item.reloadFromServer();
				}
			}
		});

		PULL.extendWatch('SALE_DELIVERY_SERVICE');
	}

	#subscribeDeliveryRequestEvents(item: ConfigurableItem): void
	{
		const deliveryRequest = this.#getDeliveryRequest(item);

		PULL.subscribe({
			moduleId: 'sale',
			command: 'onDeliveryRequestUpdate',
			callback: (params) => {
				if (deliveryRequest && deliveryRequest.id == params.ID)
				{
					item.reloadFromServer();
				}
			}
		});

		PULL.subscribe({
			moduleId: 'sale',
			command: 'onDeliveryRequestDelete',
			callback: (params) => {
				if (deliveryRequest && deliveryRequest.id == params.ID)
				{
					item.reloadFromServer();
				}
			}
		});

		PULL.extendWatch('SALE_DELIVERY_REQUEST');
	}

	#checkRequestStatus(): void
	{
		ajax.runAction('crm.timeline.deliveryactivity.checkrequeststatus');
	}

	#updateCheckRequestStatus(item: ConfigurableItem): void
	{
		const deliveryRequest = this.#getDeliveryRequest(item);
		const needCheckRequestStatus = (
			deliveryRequest
			&& deliveryRequest.isProcessed === false
		);
		if (needCheckRequestStatus && !this.#needCheckRequestStatus)
		{
			clearTimeout(this.#checkRequestStatusTimeout);
			this.#checkRequestStatusTimeout = setInterval(
				() => this.#checkRequestStatus(),
				30 * 1000
			);
		}
		else if (!needCheckRequestStatus && this.#needCheckRequestStatus)
		{
			clearTimeout(this.#checkRequestStatusTimeout);
		}

		this.#needCheckRequestStatus = needCheckRequestStatus;
	}

	#getDeliveryRequest(item: ConfigurableItem): ?Object
	{
		const dataPayload = item.getDataPayload();

		if (!Type.isObject(dataPayload.deliveryRequest))
		{
			return null;
		}

		return dataPayload.deliveryRequest;
	}

	#getDeliveryServiceIds(item: ConfigurableItem): Array
	{
		const dataPayload = item.getDataPayload();

		if (!Type.isArray(dataPayload.deliveryServiceIds))
		{
			return [];
		}

		return dataPayload.deliveryServiceIds;
	}

	#getShipmentIds(item: ConfigurableItem): Array
	{
		const dataPayload = item.getDataPayload();

		if (!Type.isArray(dataPayload.shipmentIds))
		{
			return [];
		}

		return dataPayload.shipmentIds;
	}
}
