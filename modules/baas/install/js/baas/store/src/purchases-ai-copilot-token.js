import { PurchaseDefault } from './purchase-default';
import { PurchasesDefault } from './purchases-default';
import { PurchaseAiCopilotToken } from './purchase-ai-copilot-token';
import { ResponsePurchaseDataType } from './types/response-purchase-data-type';

export class PurchasesAiCopilotToken extends PurchasesDefault
{
	createPurchaseTile(groupedPurchases: ResponsePurchaseDataType[]): PurchaseDefault
	{
		return (new PurchaseAiCopilotToken(groupedPurchases));
	}
}
