import { PurchasesDefault } from './purchases-default';
import { PurchasesAiCopilotToken } from './purchases-ai-copilot-token';
import { PurchasesDocumentgeneratorFastTransform } from './purchases-documentgenerator-fast-transform';
import { PackageItem } from './package-item';

export class PurchasesFactory
{
	create(packageItem: PackageItem): PurchasesDefault
	{
		const purchasesData = packageItem.getData().purchaseInfo.purchases;
		const purchaseData = purchasesData[0];

		if (purchaseData.serviceCode === 'ai_copilot_token')
		{
			return new PurchasesAiCopilotToken(packageItem);
		}

		if (purchaseData.serviceCode === 'documentgenerator_fast_transform')
		{
			return new PurchasesDocumentgeneratorFastTransform(packageItem);
		}

		// if (purchaseData.serviceCode === 'disk_oo_edit')
		// {
		// 	return new PurchaseDiskOoEdit(purchasesData);
		// }

		return new PurchasesDefault(packageItem);
	}
}
