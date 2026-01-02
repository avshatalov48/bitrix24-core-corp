import { showPartnerConnectForm, type ShowPartnerFormParams } from './show-partner-connect-form';
import { showPartnerOrderForm, type ShowPartnerOrderFormParams } from './show-partner-order-form';
import { showHelper } from './show-helper';

export class PartnerForm
{
	static async showConnectForm(params: ShowPartnerFormParams): void
	{
		return showPartnerConnectForm(params);
	}

	static showIntegrationOrderForm(params: ShowPartnerOrderFormParams): void
	{
		return showPartnerOrderForm(params);
	}

	static async showHelper(): void
	{
		return showHelper();
	}
}
