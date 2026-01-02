import { PurchasesDefault } from './purchases-default';
import { PurchaseDocumentgeneratorFastTransform } from './purchase-documentgenerator-fast-transform';
import { ResponsePurchaseDataType } from './types/response-purchase-data-type';
import type { ResponsePackageDataType } from './types/response-package-data-type';

export class PurchasesDocumentgeneratorFastTransform extends PurchasesDefault
{
	renderPurchases(): HTMLElement[]
	{
		const data: ResponsePackageDataType = this.getData();
		const purchases: ?ResponsePurchaseDataType[] = data.purchaseInfo.purchases;

		return [
			new PurchaseDocumentgeneratorFastTransform(purchases).getContainer(),
		];
	}
}
