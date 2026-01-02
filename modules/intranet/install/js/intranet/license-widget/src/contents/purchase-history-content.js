import { Tag } from 'main.core';
import type { PurchaseHistoryContentOptions } from '../types/options';
import { Content } from './content';

export class PurchaseHistoryContent extends Content
{
	constructor(options: PurchaseHistoryContentOptions)
	{
		super(options);
		this.setEventNamespace('BX.Bitrix24.LicenseWidget.Content.Orders');
	}

	getConfig(): Object
	{
		return {
			html: this.getLayout(),
			minHeight: '50px',
		};
	}

	getLayout(): HTMLElement
	{
		return this.cache.remember('layout', () => {
			const onclick = (event) => {
				document.querySelector('#form-purchase-history').submit();
			};

			return Tag.render`
				<div data-id="license-widget-block-orders" onclick="${onclick}" class="license-widget-item license-widget-item--secondary --pointer">
					<div class="license-widget-inner">
						<div class="license-widget-content">
							<div class="license-widget-item-icon license-widget-item-icon--order"></div>
							<div class="license-widget-item-content">
								${this.getTitle()}
							</div>
						</div>
						<div class="license-widget-item-icon__arrow-right ui-icon-set --arrow-right"/>
					</div>
					<form id="form-purchase-history" action="${this.getOptions().link}" method="post" target="_blank">
						<input name="license_key" value="${this.getOptions().hashKey}" hidden>
					</form>
				</div>
			`;
		});
	}

	getTitle(): HTMLDivElement
	{
		return this.cache.remember('title', () => {
			return Tag.render`
				<div class="license-widget-item-name">
					<span>
						${this.getOptions().text}
					</span>
				</div>
			`;
		});
	}
}