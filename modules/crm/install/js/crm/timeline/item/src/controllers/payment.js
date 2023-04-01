import {Base} from './base';
import ConfigurableItem from '../configurable-item';

export class Payment extends Base
{
	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const {action, actionType, actionData, animationCallbacks} = actionParams;

		if (actionType !== 'jsEvent')
		{
			return;
		}

		if (action === 'Payment:OpenRealization' && actionData?.paymentId)
		{
			this.#openRealization(actionData.paymentId);
		}
	}

	#openRealization(paymentId: number): void
	{
		const control = BX.Crm.EntityEditor.getDefault().getControlByIdRecursive('OPPORTUNITY_WITH_CURRENCY');
		if (!control)
		{
			return;
		}

		const paymentDocumentsControl = control.getPaymentDocumentsControl();
		if (!paymentDocumentsControl)
		{
			return;
		}

		paymentDocumentsControl._createRealizationSlider({paymentId});
	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return (
			item.getType() === 'Payment'
			|| item.getType() === 'Activity:Payment'
		);
	}
}
