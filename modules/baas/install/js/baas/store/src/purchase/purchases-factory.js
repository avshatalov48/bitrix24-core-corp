import { Type } from 'main.core';
import { ResponsePurchaseDataType } from '../types/response-purchase-data-type';
import { Purchases } from './purchases';
import { PurchaseTileDiskOoEdit } from './purchase-tile-disk-oo-edit';
import { PurchaseTileAiCopilotToken } from './purchase-tile-ai-copilot-token';
import { PurchaseTileAiCopilotAnnualToken } from './purchase-tile-ai-copilot-annual-token';
import { PurchaseTileDocumentgeneratorFastTransform } from './purchase-tile-documentgenerator-fast-transform';
import { PackageItem } from '../package-item';

export class PurchasesFactory
{
	create(packageItem: PackageItem): PurchasesDefault
	{
		const serviceCode = this.#detectServiceCode(packageItem.getData().purchaseInfo.purchases);
		const purchase = new Purchases(packageItem);

		if (serviceCode === 'ai_copilot_token')
		{
			purchase.setDefaultTileFabric((...params) => new PurchaseTileAiCopilotToken(...params));
			purchase.setAnnualTileFabric((...params) => new PurchaseTileAiCopilotAnnualToken(...params));
		}
		else if (serviceCode === 'documentgenerator_fast_transform')
		{
			purchase.setDefaultTileFabric((...params) => new PurchaseTileDocumentgeneratorFastTransform(...params));
			purchase.groupPackages = (purchases: ?ResponsePurchaseDataType[]) => {
				const groupedPackages = [];

				[...purchases]
					.forEach((purchase) => {
						[...purchase]
							.forEach((purchasedPackage) => {
								groupedPackages.push(purchasedPackage);
							})
						;
					})
				;

				return [[groupedPackages]];
			};
		}
		else if (serviceCode === 'disk_oo_edit')
		{
			purchase.setDefaultTileFabric((...params) => new PurchaseTileDiskOoEdit(...params));
		}

		return purchase;
	}

	#detectServiceCode(purchases: Array): ?string
	{
		let serviceCode = null;
		let multiple = false;

		[...purchases]
			.forEach((purchase) => {
				[...purchase]
					.forEach((purchasedPackage) => {
						if (purchasedPackage.services.length > 1)
						{
							multiple = true;
						}
						else
						{
							const thisServiceCode = purchasedPackage.services[0].code;
							if (serviceCode === null)
							{
								serviceCode = thisServiceCode;
							}
							else if (thisServiceCode !== serviceCode)
							{
								multiple = true;
							}
						}
					})
				;
			})
		;

		return multiple ? null : serviceCode;
	}
}
