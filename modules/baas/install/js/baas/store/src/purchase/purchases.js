import { Tag, Text, Loc, Type } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { DateTimeFormat } from 'main.date';
import { PackageItem } from '../package-item';
import { PurchaseTileDefault } from './purchase-tile-default';
import { PurchaseTileAnnual } from './purchase-tile-annual';
import { ResponsePurchaseDataType } from '../types/response-purchase-data-type';
import type { ResponsePackageDataType } from '../types/response-package-data-type';

export class Purchases extends EventEmitter
{
	FORMAT_DATE = 'YYYY-MM-DD';
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
			<div data-bx-package="${Text.encode(data.code)}">
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
		const groupedPackages = this.groupPackages(data.purchaseInfo.purchases);

		return [...groupedPackages]
			.map((group) => this.createPurchasedPackageTileContainer(...group))
		;
	}

	createPurchasedPackageTileContainer(groupedPackages, groupedByPurchase = false): HTMLElement
	{
		const fabric = groupedByPurchase === true
			? this.getAnnualTileFabric(groupedPackages)
			: this.getDefaultTileFabric(groupedPackages)
		;

		const container = fabric.getContainer();
		container.dataset.bxRole = 'purchase-tile';
		container.dataset.bxPurchasecodes = fabric.getPurchaseCodes();

		return container;
	}

	setDefaultTileFabric(classFabric: Function)
	{
		this.getDefaultTileFabric = classFabric;
	}

	getDefaultTileFabric(groupedPackages: ResponsePurchaseDataType[]): PurchaseTileDefault
	{
		return (new PurchaseTileDefault(groupedPackages));
	}

	setAnnualTileFabric(classFabric: Function)
	{
		this.getAnnualTileFabric = classFabric;
	}

	getAnnualTileFabric(groupedPackages: ResponsePurchaseDataType[]): PurchaseTileAnnual
	{
		return (new PurchaseTileAnnual(groupedPackages));
	}

	getData(): ResponsePackageDataType
	{
		return this.#package.getData();
	}

	groupPackages(purchases: ?ResponsePurchaseDataType[]): Object
	{
		const singlePackages = [];
		const groupedPackages = {};

		[...purchases]
			.forEach((purchase) => {
				[...purchase]
					.forEach((purchasedPackage) => {
						let id = DateTimeFormat.parse(purchasedPackage.expirationDate, false, this.FORMAT_DATE).getTime();

						if (purchase.length <= 1)
						{
							if (!singlePackages[id])
							{
								singlePackages[id] = [];
							}
							singlePackages[id].push(purchasedPackage);
						}
						else
						{
							id = purchasedPackage.purchaseCode;
							if (!groupedPackages[id])
							{
								groupedPackages[id] = {};
							}
							const time = DateTimeFormat.parse(purchasedPackage.startDate, false, this.FORMAT_DATE).getTime();
							groupedPackages[id][time] = purchasedPackage;
						}
					})
				;
			})
		;

		const packageComparator = (a, b) => {
			const getSortingRank = (pkg) => {
				const hasService = pkg.services && pkg.services[0];

				switch (true)
				{
					case hasService && pkg.services[0].current > 0 && pkg.actual === 'Y':
						return 0;
					case pkg.actual === 'N':
						return 1;
					case hasService && pkg.services[0].current === 0:
						return 2;
					default:
						return 3;
				}
			};
			const rankA = getSortingRank(a);
			const rankB = getSortingRank(b);
			if (rankA !== rankB)
			{
				return rankA - rankB;
			}
			const dateA = DateTimeFormat.parse(a.expirationDate, false, this.FORMAT_DATE).getTime();
			const dateB = DateTimeFormat.parse(b.expirationDate, false, this.FORMAT_DATE).getTime();

			return dateA - dateB;
		};

		singlePackages.sort(packageComparator);

		const finalPackages = singlePackages;

		const groupedPackageTopElements = [];
		Object
			.keys(groupedPackages)
			.forEach((key) => {
				const sortedKeys = Object
					.keys(groupedPackages[key])
					.sort((a, b) => {
						return a < b ? -1 : 1;
					})
				;
				const earliestDate = sortedKeys[0];
				const topElement = { ...groupedPackages[key][earliestDate] };
				topElement.groupKey = key;
				topElement.earliestDate = earliestDate;

				groupedPackageTopElements.push(topElement);
			});

		groupedPackageTopElements
			.sort(packageComparator)
			.forEach((topElement) => {
				let earliestDate = topElement.earliestDate;
				while (finalPackages[earliestDate])
				{
					earliestDate += 1;
				}
				finalPackages[earliestDate] = groupedPackages[topElement.groupKey];
			})
		;

		const sortedKeys = Object
			.keys(finalPackages)
		;

		return [...sortedKeys]
			.map((key) => {
				const annual = !Type.isArray(finalPackages[key]);
				let packages = finalPackages[key];
				if (annual)
				{
					packages = Object.values(finalPackages[key]);
				}

				return [packages, annual && packages.length > 1];
			})
		;
	}

	#openNewPurchase()
	{
		this.#package.openNewPurchase();
	}
}
