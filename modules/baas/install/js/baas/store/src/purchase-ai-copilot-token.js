import { Loc } from 'main.core';
import { PurchaseDefault } from './purchase-default';

export class PurchaseAiCopilotToken extends PurchaseDefault
{
	getLeftUnitsLabel(): string
	{
		return Loc.getMessage('BAAS_WIDGET_AI_PURCHASE_LEFT');
	}
}
