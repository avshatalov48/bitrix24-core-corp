import {Base} from './base';
import ConfigurableItem from '../configurable-item';
import {Type} from 'main.core';

export class SalescenterApp extends Base
{
	static isItemSupported(item: ConfigurableItem): boolean
	{
		const supportedItemTypes = [
			'Activity:Sms',
			'Activity:Notification',
			'Activity:Payment',
			'PaymentViewed',
			'PaymentNotViewed',
			'PaymentSent',
			'PaymentPaid',
			'PaymentNotPaid',
			'PaymentError',
			'PaymentSentToTerminal',
			'Activity:Delivery',
			'CustomerSelectedPaymentMethod',
		];

		return supportedItemTypes.includes(item.getType());
	}

	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const {action, actionType, actionData, animationCallbacks} = actionParams;

		if (actionType !== 'jsEvent')
		{
			return;
		}

		if (action === 'SalescenterApp:Start' && actionData)
		{
			this.#startSalescenterApp(actionData);
		}
	}

	#startSalescenterApp(actionData): void
	{
		if (
			! (
				Type.isInteger(actionData.ownerTypeId)
				&& Type.isInteger(actionData.ownerId)
				&& Type.isInteger(actionData.orderId)
				&& Type.isStringFilled(actionData.mode)
			)
		)
		{
			return;
		}

		BX.loadExt('salescenter.manager').then(() => {
			const params = {
				ownerTypeId: actionData.ownerTypeId,
				ownerId: actionData.ownerId,
				orderId: actionData.orderId,
				mode: actionData.mode,
				disableSendButton: '',
				context: 'deal',
				templateMode: 'view',
			};

			if (Type.isInteger(actionData.paymentId))
			{
				params.paymentId = actionData.paymentId;
			}

			if (Type.isInteger(actionData.shipmentId))
			{
				params.shipmentId = actionData.shipmentId;
			}

			if (Type.isStringFilled(actionData.analyticsLabel))
			{
				params.analyticsLabel = actionData.analyticsLabel;
			}

			BX.Salescenter.Manager.openApplication(params);
		});
	}
}
