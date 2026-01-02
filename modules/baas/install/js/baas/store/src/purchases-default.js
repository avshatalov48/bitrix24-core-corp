import { Tag, Text, Loc } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { PackageItem } from './package-item';
import { PurchaseAiCopilotToken } from './purchase-ai-copilot-token';
import { PurchaseDefault } from './purchase-default';
import { ResponsePurchaseDataType } from './types/response-purchase-data-type';
import type { ResponsePackageDataType } from './types/response-package-data-type';

export class PurchasesDefault extends EventEmitter
{
	#package: ?PackageItem = null;

	constructor(packageItem: PackageItem)
	{
		super();
		this.#package = packageItem;
		this.setEventNamespace('BX.Baas');
	}

	getContainer(): HTMLElement
	{
		const data: ResponsePackageDataType = this.#package.getData();

		return Tag.render`
			<div>
				<div class="ui-popupconstructor-content-main-title">
					${this.#package.getIcon()}
					<div class="ui-popupconstructor-content-item__title">${Text.encode(data.title)}</div>
				</div>
				<div class="ui-popupcomponentmaker__content-wrap">
					<div class="ui-popupcomponentmaker__content">
						${this.renderPurchases()}
						<div class="ui-popupcomponentmaker__content--section --add" onclick="${this.#openNewPurchase.bind(this)}">
							<div class="ui-popupcomponentmaker__content--section-item">
								<div class="ui-popupconstructor-content-item-wrapper">
									<div class="ui-popupconstructor-content-item-add">${Loc.getMessage('BAAS_WIDGET_PURCHASE_ADD')}</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		`;
	}

	renderPurchases(): HTMLElement[]
	{
		const data: ResponsePackageDataType = this.getData();
		const purchases: ?ResponsePurchaseDataType[] = data.purchaseInfo.purchases;
		const groupedPurchases = {};

		purchases.forEach((purchase) => {
			const id = purchase.expirationDate;
			if (!groupedPurchases[id])
			{
				groupedPurchases[id] = [];
			}
			groupedPurchases[id].push(purchase);
		});

		return Object.values(groupedPurchases).map((purchases) => this.createPurchaseTile(purchases).getContainer());
	}

	createPurchaseTile(groupedPurchases): PurchaseDefault
	{
		return (new PurchaseDefault(groupedPurchases));
	}

	getData(): ResponsePackageDataType
	{
		return this.#package.getData();
	}

	#openNewPurchase()
	{
		window.open(this.#package.getData().purchaseUrl, '_blank');
	}
}
