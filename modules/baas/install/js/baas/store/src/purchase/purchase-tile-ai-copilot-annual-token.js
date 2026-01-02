import { Loc } from 'main.core';
import {
	ResponsePurchasedPackageDataType,
	ServiceInPurchasedPackageType,
} from '../types/response-purchased-package-data-type';
import { PurchaseTileAnnual } from './purchase-tile-annual';

export class PurchaseTileAiCopilotAnnualToken extends PurchaseTileAnnual
{
	getLeftUnitsString(serviceGrouped: ServiceInPurchasedPackageType, pack: ResponsePurchasedPackageDataType): string
	{
		return `
			${Loc.getMessage('BAAS_WIDGET_AI_PURCHASE_LEFT_STATUS', {
			'#left#': `<span class="ui-popupconstructor-content-item-num">${serviceGrouped.current}</span>`,
			'#total#': `<span class="ui-popupconstructor-content-item-num">${serviceGrouped.maximal}</span>`,
			'#date#': `<span>${pack.expirationDate}</span>`,
		})}`;
	}
}
