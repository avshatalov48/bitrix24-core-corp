import { Loc, Tag } from 'main.core';
import { DateTimeFormat } from 'main.date';
import { PurchaseTileDefault } from './purchase-tile-default';
import { ResponsePurchasedPackageDataType } from '../types/response-purchased-package-data-type';

export class PurchaseTileDocumentgeneratorFastTransform extends PurchaseTileDefault
{
	getContainer(): HTMLElement
	{
		const pack: ResponsePurchasedPackageDataType = this.prepareGroupedPurchases(this.getGroupedPurchases());
		pack.startDate = DateTimeFormat.format(DateTimeFormat.getFormat('LONG_DATE_FORMAT'), pack.startDate);
		pack.expirationDate = DateTimeFormat.format(DateTimeFormat.getFormat('LONG_DATE_FORMAT'), pack.expirationDate);

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
										${this.renderStatusLabel(pack)}
										<button class="ui-popupconstructor-content-item-menu" style="display: none;" type="button"></button>
									</div>
								</div>
								<div class="ui-popupconstructor-content-item-limit">
									<span>${Loc.getMessage('BAAS_WIDGET_DFT_PURCHASE_LEFT')} </span>
									<span class="ui-popupconstructor-content-item-date">${pack.expirationDate}</span>
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
								<div class="ui-popupconstructor-content-item__title">${Loc.getMessage('BAAS_WIDGET_PURCHASE_TITLE')}</div>
								<div class="ui-popupconstructor-content-item-subject">
									<div class="ui-label ui-label-success ui-label-sm --active ui-label-fill">
										<div class="ui-label-status"></div>
										<span class="ui-label-inner">${Loc.getMessage('BAAS_WIDGET_PURCHASE_IS_ACTIVE')}</span>
									</div>
								</div>
							</div>
							<div class="ui-popupconstructor-content-item-limit">
								<span>${Loc.getMessage('BAAS_WIDGET_DFT_PURCHASE_LEFT')} </span>
								<span class="ui-popupconstructor-content-item-date">${pack.expirationDate}</span>
							</div>
						</div>
					</div>
				</div>
			</div>
		`;
	}
}
