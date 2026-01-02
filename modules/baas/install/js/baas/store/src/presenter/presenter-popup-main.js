import { Loc, Dom, Type } from 'main.core';
import { PackageItem } from '../package-item';
import { EventEmitter, BaseEvent } from 'main.core.events';
import { ResponseAdsInfoDataType } from '../types/response-ads-info-data-type';
import type { WidgetDataBodyType, WidgetDataHeaderType } from '../types/widget-data-type';
import { PresenterPopup } from './presenter-popup';
import { Actions, Icon, Set } from 'ui.icon-set.api.core';
import { ResponseDataType } from '../types/response-data-type';
import { WidgetIcon } from '../widget-icon';
import { PopupWithHeader } from 'ui.popup-with-header';

export class PresenterPopupMain extends PresenterPopup
{
	initialize(): void
	{
		EventEmitter.subscribe(
			EventEmitter.GLOBAL_TARGET,
			'BX.Baas:onPurchaseShown',
			(event: BaseEvent) => this.#onPurchaseShown(event),
		);
		EventEmitter.subscribe(
			EventEmitter.GLOBAL_TARGET,
			'BX.Baas:onPurchaseHidden',
			(event: BaseEvent) => this.#onPackageHidden(event),
		);
	}

	#onPurchaseShown(event: BaseEvent): void
	{
		const packageItem: PackageItem = event.getData().package;
		if (this.cache.has('baas-popup') && this.getPopup()?.getContentContainer().contains(packageItem.getContainer()))
		{
			this.getPopup().setAutoHide(false);
			this.getPopup().setClosingByEsc(false);
		}
	}

	#onPackageHidden(event: BaseEvent)
	{
		const packageItem: PackageItem = event.getData().package;
		if (this.cache.has('baas-popup') && this.getPopup().getContentContainer().contains(packageItem.getContainer()))
		{
			this.getPopup().setAutoHide(true);
			this.getPopup().setClosingByEsc(true);
		}
	}

	getIconContainer(data: ResponseDataType): ?HTMLElement
	{
		return this.cache.remember('baas-popup-icon', () => {
			return new WidgetIcon({
				icon: Actions.CHEVRON_LEFT,
				size: 22,
				color: '#fff',
				events: {
					click: () => {
						EventEmitter.emit(this, 'BX.Baas:onClickBack');
						this.hide();
					},
				},
			}).render();
		});
	}

	getWidgetHeader(data: ResponseDataType): WidgetDataHeaderType
	{
		return {
			title: Loc.getMessage('BAAS_WIDGET_TITLE'),
			subtitle: this.getSubtitleContainer(),
		};
	}

	getSubtitleContainer(): String | HTMLElement
	{
		return '';
	}

	getBody(data: ResponseDataType): WidgetDataBodyType
	{
		const adsInfo: ?ResponseAdsInfoDataType = data.adsInfo;
		const showMore = Type.isStringFilled(adsInfo?.featurePromotionCode);

		return adsInfo && adsInfo.title ? {
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
			moreLabel: showMore ? Loc.getMessage('BAAS_WIDGET_MORE_LINK_TITLE') : '',
			code: showMore ? adsInfo.featurePromotionCode : null,
		} : {
			title: Loc.getMessage('BAAS_WIDGET_DESCRIPTION'),
			roundContent: new Icon({ icon: Set.QR_CODE_1 }).render(),
			moreLabel: '',
			code: null,
		};
	}

	adjustPopupOnShow(popup: PopupWithHeader)
	{
		Dom.addClass(popup.getPopup().contentContainer, '--baas-widget --baas-common');
	}
}
