import { Loc, Tag } from 'main.core';
import { DateTimeFormat } from 'main.date';
import { Label, LabelSize } from 'ui.label';
import { ActiveAndFuturePurchasesResult } from '../types/purchase-active-and-future-data-type';
import {
	ResponsePurchasedPackageDataType,
	ServiceInPurchasedPackageType,
} from '../types/response-purchased-package-data-type';
import { PurchaseTileDefault } from './purchase-tile-default';

export class PurchaseTileAnnual extends PurchaseTileDefault
{
	#groupPackages(purchasedPackages: ResponsePurchasedPackageDataType[]): Object
	{
		const rawActivePacks = {};
		const rawInactivePacks = {};
		let expirationDateObj = new Date();

		purchasedPackages.forEach((rawPack: ResponsePurchasedPackageDataType) => {
			const actual = (rawPack.actual === 'Y' || rawPack.actual === true);
			const pack = { ...rawPack };
			if (actual)
			{
				const expDateActive = DateTimeFormat.parse(rawPack.expirationDate, false, this.FORMAT_DATE).getTime();
				rawActivePacks[expDateActive] = rawActivePacks[expDateActive] ?? [];
				rawActivePacks[expDateActive].push(pack);
			}
			else
			{
				const startDate = DateTimeFormat.parse(rawPack.startDate, false, this.FORMAT_DATE).getTime();
				rawInactivePacks[startDate] = rawInactivePacks[startDate] ?? [];
				rawInactivePacks[startDate].push(pack);
			}

			const expDate = DateTimeFormat.parse(rawPack.expirationDate, false, this.FORMAT_DATE);
			if (expDate > expirationDateObj)
			{
				expirationDateObj = expDate;
			}
		});

		return { rawActivePacks, rawInactivePacks, expirationDateObj };
	}

	#calculateActiveResult(rawActivePacks): Object
	{
		const activePackKeys = Object.keys(rawActivePacks);
		const active = {
			current: 0,
			maximal: 0,
			expirationDateObject: new Date(),
			expirationDate: '',
		};

		activePackKeys.forEach((key) => {
			const packs = rawActivePacks[key];
			packs.forEach((pack) => {
				Object.keys(pack.services).forEach((serviceCode: string) => {
					const service: ServiceInPurchasedPackageType = pack.services[serviceCode];
					active.current += parseInt(service.current, 10);
					active.maximal += parseInt(service.maximal, 10);
				});
				const expDateActive = DateTimeFormat.parse(pack.expirationDate, false, this.FORMAT_DATE);
				if (active.expirationDateObject < expDateActive)
				{
					active.expirationDateObject = expDateActive;
				}
			});
		});
		active.expirationDate = DateTimeFormat.format(
			DateTimeFormat.getFormat('DAY_MONTH_FORMAT'),
			active.expirationDateObject,
		);

		return active;
	}

	#calculateNextResult(rawInactivePacks): Object
	{
		const inactivePackKeys = Object
			.keys(rawInactivePacks)
			.sort((a, b) => {
				return a < b ? -1 : 1;
			});

		const next = {
			current: 0,
			maximal: 0,
			startDateObject: new Date(),
			startDate: '',
		};

		const firstDate = inactivePackKeys[0];
		const futurePacks = rawInactivePacks[firstDate];

		futurePacks.forEach((pack) => {
			Object.keys(pack.services).forEach((serviceCode: string) => {
				const service: ServiceInPurchasedPackageType = pack.services[serviceCode];
				next.current += parseInt(service.current, 10);
				next.maximal += parseInt(service.maximal, 10);
			});
			next.startDate = pack.startDate;
		});

		return next;
	}

	#prepareActiveAndFuturePurchases(purchasedPacks: ResponsePurchasedPackageDataType[]): ActiveAndFuturePurchasesResult
	{
		const { rawActivePacks, rawInactivePacks, expirationDateObj } = this.#groupPackages(purchasedPacks);

		const result = {
			expirationDate: DateTimeFormat.format(DateTimeFormat.getFormat('LONG_DATE_FORMAT'), expirationDateObj),
		};

		const active = this.#calculateActiveResult(rawActivePacks);
		if (active)
		{
			result.active = active;
		}
		else
		{
			const next = this.#calculateNextResult(rawInactivePacks);
			if (next)
			{
				result.next = next;
			}
		}

		return result;
	}

	getContainer(): HTMLElement
	{
		const packageInfo = this.#prepareActiveAndFuturePurchases(this.getGroupedPurchases());

		const count = this.getGroupedPurchases().length;
		const modifiedClass = count > 2 ? '--more' : '--two';
		const current = packageInfo.active ? packageInfo.active.current : packageInfo.next.current;
		const maximal = packageInfo.active ? packageInfo.active.maximal : packageInfo.next.maximal;
		const theDate = packageInfo.active ? packageInfo.active.expirationDate : packageInfo.next.startDate;
		const expirationDate = packageInfo.expirationDate;

		return Tag.render`
			<div class="ui-popupcomponentmaker__content--section ${modifiedClass}">
				<div class="ui-popupcomponentmaker__content--section-item">
					<div class="ui-popupconstructor-content-item-wrapper">
						<div class="ui-popupconstructor-content-item-wrapper_information">
							<div class="ui-popupconstructor-content-item-wrapper-title">
								<div class="ui-popupconstructor-content-item__title">
									${Loc.getMessage('BAAS_WIDGET_PURCHASE_TITLE')}
								</div>
								<div class="ui-popupconstructor-content-item-subject">
									${this.#getStatusLabels(packageInfo).map((label: Label) => label.render())}
								</div>
							</div>
							<div class="ui-popupconstructor-content-item-progressbar">${this.createProgressBar(current, maximal).getContainer()}</div>
							${packageInfo.active ? Tag.render`
								<div class="ui-popupconstructor-content-item-limit">
									${Loc.getMessage('BAAS_WIDGET_PURCHASE_LEFT_STATUS', {
										'#left#': `<span class="ui-popupconstructor-content-item-num">${current}</span>`,
										'#total#': `<span class="ui-popupconstructor-content-item-num">${maximal}</span>`,
										'#date#': `<span>${theDate}</span>`,
									})}
								</div>
							` : Tag.render`
								<div class="ui-popupconstructor-content-item-limit">
									<span>${this.getFutureUnitsLabel()} </span>
										${Loc.getMessage('BAAS_WIDGET_PURCHASE_WILL_BE_AVAILABLE_STATUS', {
											'#left#': `<span class="ui-popupconstructor-content-item-num">${current}</span>`,
											'#total#': `<span class="ui-popupconstructor-content-item-num">${maximal}</span>`,
											'#date#': `<span class="ui-popupconstructor-content-item-date">${theDate}</span>`,
										})}
								</div>
							`}
							<div class="ui-popupconstructor-content-item-limit">
								${Loc.getMessage('BAAS_WIDGET_PURCHASE_ANNUAL_LIFETIME', {
									'#date#': `<span class="ui-popupconstructor-content-item-date">${expirationDate}</span>`,
								})}
							</div>
						</div>
					</div>
				</div>
			</div>
		`
		;
	}

	getFutureUnitsLabel(): string
	{
		return Loc.getMessage('BAAS_WIDGET_PURCHASES_FUTURE_UNITS');
	}

	#getStatusLabels(purchasesResult: ActiveAndFuturePurchasesResult): Array<Label>
	{
		const labels: Array<Label> = [];
		const isActual = Boolean(purchasesResult.active);

		if (!isActual)
		{
			labels.push(this.#createLabel('BAAS_WIDGET_PURCHASES_ARE_PAID', '--paid'));

			return labels;
		}

		const hasZeroBalance = isActual && purchasesResult.active.current === 0;

		if (isActual && !hasZeroBalance && ((purchasesResult.active.current / purchasesResult.active.maximal) * 100 <= 20))
		{
			labels.push(this.#createLabel('BAAS_WIDGET_PURCHASE_LIMIT_IS_ALMOST_EXCEEDED', '--almost'));
		}

		if (hasZeroBalance)
		{
			labels.push(this.#createLabel('BAAS_WIDGET_PURCHASE_LIMIT_IS_EXCEEDED_THIS_MONTH', '--exceeded'));
		}

		labels.unshift(this.#createLabel('BAAS_WIDGET_PURCHASE_IS_ACTIVE', '--active'));

		return labels;
	}

	#createLabel(message: String, styleClass: String): Label
	{
		return new Label({
			text: Loc.getMessage(message),
			size: LabelSize.SM,
			fill: true,
			customClass: styleClass,
		});
	}
}
