import { Tag, Loc } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { ProgressBar } from 'ui.progressbar';
import { ResponsePurchaseDataType } from './types/response-purchase-data-type';

export class PurchaseDefault extends EventEmitter
{
	#purchases: ?ResponsePurchaseDataType[] = [];

	constructor(purchases: ResponsePurchaseDataType[])
	{
		super();
		this.#purchases = purchases;
		this.setEventNamespace('BX.Baas');
	}

	prepareGroupedPurchases(purchases: ResponsePurchaseDataType[]): ResponsePurchaseDataType
	{
		const purchase = {
			current: 0,
			maximal: 0,
			startDate: 0,
			expirationDate: 0,
			actual: 'N',
		};

		purchases.forEach((p: ResponsePurchaseDataType) => {
			purchase.current += parseInt(p.current, 10);
			purchase.maximal += parseInt(p.maximal, 10);
			purchase.startDate = purchase.startDate > p.startDate ? purchase.startDate : p.startDate;
			purchase.expirationDate = purchase.expirationDate > p.expirationDate ? purchase.expirationDate : p.expirationDate;
			purchase.actual = p.actual === 'Y' ? 'Y' : purchase.actual;
		});

		return purchase;
	}

	getGroupedPurchases(): ResponsePurchaseDataType[]
	{
		return this.#purchases;
	}

	getContainer(): HTMLElement
	{
		const purchase: ResponsePurchaseDataType = this.prepareGroupedPurchases(this.#purchases);
		const count = this.getGroupedPurchases().length;
		const multiple = this.getGroupedPurchases().length > 1;

		if (multiple)
		{
			const modifiedClass = count > 2 ? '--more' : '--two';

			return Tag.render`
			<div class="ui-popupcomponentmaker__content--section ${modifiedClass}">
				<div class="ui-popupcomponentmaker__content--section-item">
					<div class="ui-popupconstructor-content-item-wrapper">
						<div class="ui-popupconstructor-content-item-wrapper_information">
							<div class="ui-popupconstructor-content-item-wrapper-title">
								<div class="ui-popupconstructor-content-item__title">
									${[Loc.getMessage('BAAS_WIDGET_PURCHASES_TITLE'), ': ', count].join('')}
								</div>
								<div class="ui-popupconstructor-content-item-subject">
									<div class="ui-label ui-label-success ui-label-sm --active ui-label-fill">
										<div class="ui-label-status"></div>
										<span class="ui-label-inner">${Loc.getMessage('BAAS_WIDGET_PURCHASES_ARE_ACTIVE')}</span>
									</div>
									<button class="ui-popupconstructor-content-item-menu" style="display: none;" type="button"></button>
								</div>
							</div>
							<div class="ui-popupconstructor-content-item-progressbar">${this.#createProgressBar(purchase.current, purchase.maximal).getContainer()}</div>
							<div class="ui-popupconstructor-content-item-limit">
								<span>${this.getLeftUnitsLabel()} </span>
								${Loc.getMessage('BAAS_WIDGET_PURCHASE_LEFT_STATUS', {
									'#left#': `<span class="ui-popupconstructor-content-item-num">${purchase.current}</span>`,
									'#total#': `<span class="ui-popupconstructor-content-item-num">${purchase.maximal}</span>`,
									'#date#': `<span class="ui-popupconstructor-content-item-date">${purchase.expirationDate}</span>`,
								})}
							</div>
						</div>
					</div>
				</div>
			</div>`;
		}

		return Tag.render`
			<div class="ui-popupcomponentmaker__content--section">
				<div class="ui-popupcomponentmaker__content--section-item">
					<div class="ui-popupconstructor-content-item-wrapper">
						<div class="ui-popupconstructor-content-item-wrapper_information">
							<div class="ui-popupconstructor-content-item-wrapper-title">
								<div class="ui-popupconstructor-content-item__title">
									${Loc.getMessage('BAAS_WIDGET_PURCHASE_TITLE')}
								</div>
								<div class="ui-popupconstructor-content-item-subject">
									<div class="ui-label ui-label-success ui-label-sm --active ui-label-fill" ${purchase.actual === 'Y' ? '' : 'style="display: none;"'}>
										<div class="ui-label-status"></div>
										<span class="ui-label-inner">${Loc.getMessage('BAAS_WIDGET_PURCHASE_IS_ACTIVE')}</span>
									</div>
									<div class="ui-label ui-label-success ui-label-sm --paid ui-label-fill" ${purchase.actual === 'Y' ? 'style="display: none;"' : ''}>
										<div class="ui-label-status"></div>
										<span class="ui-label-inner">${Loc.getMessage('BAAS_WIDGET_PURCHASES_ARE_PAID')}</span>
									</div>
									<button class="ui-popupconstructor-content-item-menu" style="display: none;" type="button"></button>
								</div>
							</div>
							<div class="ui-popupconstructor-content-item-progressbar">${this.#createProgressBar(purchase.current, purchase.maximal).getContainer()}</div>
							<div class="ui-popupconstructor-content-item-limit">
								<span>${this.getLeftUnitsLabel()} </span>
								${Loc.getMessage('BAAS_WIDGET_PURCHASE_LEFT_STATUS', {
									'#left#': `<span class="ui-popupconstructor-content-item-num">${purchase.current}</span>`,
									'#total#': `<span class="ui-popupconstructor-content-item-num">${purchase.maximal}</span>`,
									'#date#': `<span class="ui-popupconstructor-content-item-date">${purchase.expirationDate}</span>`,
								})}
							</div>
						</div>
					</div>
				</div>
			</div>`
		;
	}

	getLeftUnitsLabel(): string
	{
		return Loc.getMessage('BAAS_WIDGET_PURCHASE_LEFT');
	}

	getData(): ResponsePurchaseDataType[]
	{
		return this.#purchases;
	}

	#createProgressBar(current: number, maximal: number): ProgressBar
	{
		return new ProgressBar({
			value: Math.round(current / maximal * 100),
			size: 4,
		});
	}
}
