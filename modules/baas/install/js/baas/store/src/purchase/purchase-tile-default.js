import { Tag, Loc, Type } from 'main.core';
import { DateTimeFormat } from 'main.date';
import { EventEmitter } from 'main.core.events';
import { ProgressBar } from 'ui.progressbar';
import { ResponsePurchasedPackageDataType, ServiceInPurchasedPackageType } from '../types/response-purchased-package-data-type';

export class PurchaseTileDefault extends EventEmitter
{
	FORMAT_DATE: String = 'YYYY-MM-DD'; // 'Y-m-d';
	#purchasedPackages: ?ResponsePurchasedPackageDataType[] = [];

	constructor(purchasedPackages: ResponsePurchasedPackageDataType[])
	{
		super();

		this.#purchasedPackages = purchasedPackages;
		this.setEventNamespace('BX.Baas');
	}

	prepareGroupedPurchases(purchasedPackages: ResponsePurchasedPackageDataType[]): ResponsePurchasedPackageDataType
	{
		const groupedPackageInfo = {
			startDate: new Date(),
			expirationDate: new Date(),
			actual: 'N',
			services: {},
		};

		purchasedPackages.forEach((pack: ResponsePurchasedPackageDataType) => {
			const startDate = DateTimeFormat.parse(pack.startDate, false, this.FORMAT_DATE);
			const expirationDate = DateTimeFormat.parse(pack.expirationDate, false, this.FORMAT_DATE);

			if (startDate < groupedPackageInfo.startDate)
			{
				groupedPackageInfo.startDate = startDate;
			}

			if (groupedPackageInfo.expirationDate < expirationDate)
			{
				groupedPackageInfo.expirationDate = expirationDate;
			}

			const actual = (pack.actual === 'Y' || pack.actual === true);
			groupedPackageInfo.actual = actual ? 'Y' : groupedPackageInfo.actual;
			const services = Type.isArray(pack.services) ? pack.services : Object.values(pack.services);

			services
				.forEach((service: ServiceInPurchasedPackageType) => {
					const serviceCode = service.code;
					groupedPackageInfo.services[serviceCode] = groupedPackageInfo.services[serviceCode] || {
						code: serviceCode,
						current: 0,
						maximal: 0,
					};
					groupedPackageInfo.services[serviceCode].current += parseInt(service.current, 10);
					groupedPackageInfo.services[serviceCode].maximal += parseInt(service.maximal, 10);
				})
			;
		});

		return groupedPackageInfo;
	}

	getGroupedPurchases(): ResponsePurchasedPackageDataType[]
	{
		return this.#purchasedPackages;
	}

	getPurchaseCodes(): string
	{
		return this.getGroupedPurchases().map((pack: ResponsePurchasedPackageDataType) => pack.purchaseCode).join(',');
	}

	getContainer(): HTMLElement
	{
		const pack: ResponsePurchasedPackageDataType = this.prepareGroupedPurchases(this.getGroupedPurchases());
		pack.startDate = DateTimeFormat.format(DateTimeFormat.getFormat('LONG_DATE_FORMAT'), pack.startDate);
		pack.expirationDate = DateTimeFormat.format(DateTimeFormat.getFormat('LONG_DATE_FORMAT'), pack.expirationDate);

		const count = this.getGroupedPurchases().length;
		const multiple = this.getGroupedPurchases().length > 1;

		const serviceGrouped: ServiceInPurchasedPackageType = {
			current: 0,
			maximal: 0,
			active: 'N',
		};

		Object
			.keys(pack.services)
			.forEach((serviceCode: string) => {
				const service: ServiceInPurchasedPackageType = pack.services[serviceCode];
				serviceGrouped.current += parseInt(service.current, 10);
				serviceGrouped.maximal += parseInt(service.maximal, 10);
			})
		;

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
								<div class="ui-popupconstructor-content-item-progressbar">${this.createProgressBar(serviceGrouped.current, serviceGrouped.maximal).getContainer()}</div>
								<div class="ui-popupconstructor-content-item-limit">
									${this.getLeftUnitsString(serviceGrouped, pack)}
								</div>
							</div>
						</div>
					</div>
				</div>
			`;
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
									${this.renderStatusLabel(pack)}
									<button class="ui-popupconstructor-content-item-menu" style="display: none;" type="button"></button>
								</div>
							</div>
							<div class="ui-popupconstructor-content-item-progressbar">${this.createProgressBar(serviceGrouped.current, serviceGrouped.maximal).getContainer()}</div>
							<div class="ui-popupconstructor-content-item-limit">
								${this.getLeftUnitsString(serviceGrouped, pack)}
							</div>
						</div>
					</div>
				</div>
			</div>
		`;
	}

	getLeftUnitsString(serviceGrouped: ServiceInPurchasedPackageType, pack: ResponsePurchasedPackageDataType): string
	{
		return `
			${Loc.getMessage('BAAS_WIDGET_PURCHASE_LEFT_STATUS', {
			'#left#': `<span class="ui-popupconstructor-content-item-num">${serviceGrouped.current}</span>`,
			'#total#': `<span class="ui-popupconstructor-content-item-num">${serviceGrouped.maximal}</span>`,
			'#date#': String(pack.expirationDate),
		})}
		`;
	}

	getData(): ResponsePurchasedPackageDataType[]
	{
		return this.#purchasedPackages;
	}

	createProgressBar(current: number, maximal: number): ProgressBar
	{
		return new ProgressBar({
			value: Math.round(current / maximal * 100),
			size: 4,
		});
	}

	renderStatusLabel(pack: ResponsePurchasedPackageDataType): HTMLElement
	{
		const firstService = Object.values(pack.services)[0];
		const isActual = pack.actual === 'Y';
		const current = firstService.current;

		if (isActual && current > 0)
		{
			const limitPercentLeft = (current / firstService.maximal) * 100;
			if (limitPercentLeft <= 20)
			{
				return Tag.render`
					<div class="ui-label ui-label-success ui-label-sm --active ui-label-fill">
						<div class="ui-label-status"></div>
						<span class="ui-label-inner">${Loc.getMessage('BAAS_WIDGET_PURCHASE_LIMIT_IS_ALMOST_EXCEEDED')}</span>
					</div>
				`;
			}

			return Tag.render`
				<div class="ui-label ui-label-success ui-label-sm --active ui-label-fill">
					<div class="ui-label-status"></div>
					<span class="ui-label-inner">${Loc.getMessage('BAAS_WIDGET_PURCHASE_IS_ACTIVE')}</span>
				</div>
			`;
		}

		if (isActual && current <= 0)
		{
			return Tag.render`
				<div class="ui-label ui-label-sm --exceeded ui-label-fill">
					<div class="ui-label-status"></div>
					<span class="ui-label-inner">${Loc.getMessage('BAAS_WIDGET_PURCHASE_LIMIT_IS_EXCEEDED')}</span>
				</div>
			`;
		}

		return Tag.render`
			<div class="ui-label ui-label-success ui-label-sm --paid ui-label-fill">
				<div class="ui-label-status"></div>
				<span class="ui-label-inner">${Loc.getMessage('BAAS_WIDGET_PURCHASES_ARE_PAID')}</span>
			</div>
		`;
	}
}
