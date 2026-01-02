import { BaseCache, Cache, Loc, Text, Dom, Type, Tag } from 'main.core';
import { Button, ButtonColor, ButtonSize } from 'ui.buttons';
import { BaseEvent } from 'main.core.events';
import type { AjaxResponse, AjaxSuccess } from 'main.core';
import { Analytics } from '../analytics';
import { PackageTemplateManager } from '../package-template-manager';
import { ResponseDataType } from '../types/response-data-type';
import type { WidgetDataType, WidgetDataHeaderType, WidgetDataBodyType } from '../types/widget-data-type';
import { PopupWithHeader } from 'ui.popup-with-header';
import { Popup } from 'main.popup';
import { Icon, Set } from 'ui.icon-set.api.core';
import { PresenterDefault } from './presenter-default';
import { ResponseServiceDataType } from '../types/response-service-data-type';
import { ResponseAdsInfoDataType } from '../types/response-ads-info-data-type';

export class PresenterPopup extends PresenterDefault
{
	static justCounter: Number = 0;

	cache: BaseCache = new Cache.MemoryCache();

	constructor(
		serviceCode: string,
		serviceData: ResponseServiceDataType,
		dataProviderPromiseCreator: ?Function = null,
	)
	{
		super(serviceCode, serviceData, dataProviderPromiseCreator);
		this.#setServiceData(serviceData);
		this.initialize();
	}

	initialize(): void
	{}

	adjustServiceData(serviceData: ResponseServiceDataType): void
	{
		this.#setServiceData(serviceData);

		this.getSubtitleContainer().innerHTML = Text.encode(
			serviceData.isActive === true ? serviceData.activeSubtitle : serviceData.inactiveSubtitle,
		);
	}

	#setServiceData(serviceData: ResponseServiceDataType): void
	{
		this.serviceData = { ...serviceData };
	}

	#getAnalytic(): Analytics
	{
		return this.cache.remember('analyticObject', () => {
			return Analytics.createByServiceCode(this.serviceCode);
		});
	}

	getIconContainer(data: ResponseDataType): ?HTMLElement
	{
		return this.cache.remember('baas-popup-icon', () => {
			const iconParams: { className: string, color: ?string, style: ?Object } = { ...this.serviceData.icon };
			const iconClass = iconParams.className;
			const params = { icon: (Set[iconClass] ?? Set.QR_CODE_1 ?? null) };

			if (params)
			{
				if (iconParams.color)
				{
					params.color = iconParams.color;
				}

				return (new Icon(params)).render();
			}

			return Dom.addClass(
				Tag.render`<div class="ui-popupconstructor-content-item__icon ui-icon-set"></div>`,
				iconClass,
			);
		});
	}

	getWidgetHeader(data: ResponseDataType): WidgetDataHeaderType
	{
		return {
			title: this.serviceData.title,
			subtitle: this.getSubtitleContainer(),
		};
	}

	getSubtitleContainer(): String | HTMLElement
	{
		return this.cache.remember('baas-popup-subtitle', () => {
			return Tag.render`<span>${Text.encode(this.serviceData.isActive ? this.serviceData.activeSubtitle : this.serviceData.inactiveSubtitle)}</span>`;
		});
	}

	getBody(data: ResponseDataType): WidgetDataBodyType
	{
		const adsInfo: ?ResponseAdsInfoDataType = data.adsInfo;

		return adsInfo ? {
			title: adsInfo.title,
			subtitle: adsInfo.subtitle,
			subtitleDescription: adsInfo.subtitleDescription,
			roundContent: {
				posterUrl: adsInfo.iconUrl,
				videos: [{
					url: adsInfo.videoUrl,
					type: adsInfo.videoFileType,
				}],
			},
			moreLabel: Loc.getMessage('BAAS_WIDGET_MORE_LINK_TITLE'),
			code: adsInfo.featurePromotionCode || this.serviceData.featurePromotionCode,
		} : {
			title: this.serviceData.description,
			roundContent: this.getIconContainer(),
			moreLabel: Loc.getMessage('BAAS_WIDGET_MORE_LINK_TITLE'),
			code: this.serviceData.featurePromotionCode,
		};
	}

	getEmergencyButton(data: ResponseDataType): ?Button
	{
		if (Type.isArray(data?.packages))
		{
			return null;
		}

		return new Button({
			text: Loc.getMessage('BAAS_WIDGET_RELOAD_PACKAGES'),
			color: ButtonColor.LIGHT_BORDER,
			size: ButtonSize.SMALL,
			onclick: () => {
				this.reload();
			},
			round: true,
			noCaps: true,
		});
	}

	#getLastBoundElement(): ?HTMLElement
	{
		return (this.cache.has('boundLastElement') ? this.cache.get('boundLastElement') : null);
	}

	#getWidgetData(): Promise
	{
		return new Promise((resolve, reject) => {
			if (this.cache.has('widgetData'))
			{
				resolve({ data: this.cache.get('widgetData') });
			}
			else if (Type.isFunction(this.dataProviderPromiseCreator))
			{
				this.dataProviderPromiseCreator()
					.then((response: AjaxResponse<AjaxSuccess>) => {
						response.data = this.#convertIntoWidgetDataType(response.data);
						resolve(response);
					})
					.catch(reject)
				;
			}
			else
			{
				// eslint-disable-next-line unicorn/prefer-type-error
				throw new Error('Baas popup presenter: data provider is not defined');
			}
		});
	}

	#getPopupWithHeader(): PopupWithHeader
	{
		return this.cache.remember('baas-popup', () => {
			const popup = new PopupWithHeader({
				target: this.#getLastBoundElement(),
				id: `baas-components-maker-${this.constructor.justCounter++}`,
				width: 344,
				content: [],
				asyncData: this.#getWidgetData(),
				template: new PackageTemplateManager(),
				analyticsCallback: (event, additionalParameter) => {
					this.#getAnalytic().onPlayerEvents(event, additionalParameter);
				},
				popupOptions: {
					autoHide: true,
					closeByEsc: true,
					events: {
						onShow: (event: BaseEvent): void => {
							const targetPopup = event.getTarget();
							const targetPos = Dom.getPosition(targetPopup.bindElement);
							const popupWidth = targetPopup.getPopupContainer().offsetWidth;
							const offsetLeft = (targetPos.width / 2) - (popupWidth / 2);
							const angleShift = Popup.getOption('angleLeftOffset') - Popup.getOption('angleMinTop');

							targetPopup.setAngle({ offset: popupWidth / 2 - angleShift });
							targetPopup.setOffset({ offsetLeft: offsetLeft + Popup.getOption('angleLeftOffset') });

							this.#getAnalytic().activate().onShowFrom(
								targetPopup.bindElement.dataset.bxAnalyticContextLabel ?? Analytics.CONTEXT_IS_NOT_SET,
							);
						},
						onClose: () => {
							this.#getAnalytic().deactivate();
						},
					},
				},
			});

			this.adjustPopupOnShow(popup);

			return popup;
		});
	}

	adjustPopupOnShow(popup: PopupWithHeader)
	{
		Dom.addClass(popup.getPopup().getContentContainer(), '--baas-widget --baas-specified');
	}

	#convertIntoWidgetDataType(data: ResponseDataType): WidgetDataType
	{
		return {
			header: {
				icon: this.getIconContainer(data),
				top: this.getWidgetHeader(data),
				info: this.getBody(data),
				button: this.getEmergencyButton(data),
			},
			items: data.packages,
		};
	}

	getPopup(): Popup
	{
		return this.#getPopupWithHeader().getPopup();
	}

	show(node: ?HTMLElement): void
	{
		const popup = this.#getPopupWithHeader().getPopup();

		if (node && node !== popup.bindElement)
		{
			popup.setBindElement(node);
		}
		popup.show();
	}

	toggle(node: ?HTMLElement): void
	{
		const popup = this.getPopup();
		if (popup.isShown())
		{
			this.hide();
		}
		else
		{
			this.show(node);
		}
	}

	hide(): void
	{
		const popup = this.getPopup();
		popup.close();
	}

	reload()
	{
		const popup = this.getPopup();
		popup.close();
		this.cache.delete('widgetData');
		this.cache.delete('baas-popup');
		this.cache.delete('baas-popup-icon');
		this.cache.delete('baas-popup-subtitle');
		this.show(popup.bindElement);
	}
}
