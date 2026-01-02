import { Tag } from 'main.core';
import { FeaturePromotersRegistry } from 'ui.info-helper';
import { Content } from './content';

export class PartnerContent extends Content
{
	constructor(options)
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
			const onclick = () => {
				if (this.getOptions().landingCode)
				{
					FeaturePromotersRegistry.getPromoter({ code: this.getOptions().landingCode }).show();
				}
				else
				{
					window.open(this.getOptions().link);
				}
			};

			return Tag.render`
				<div data-id="license-widget-block-orders" onclick="${onclick}" class="license-widget-item license-widget-item--secondary --pointer">
					<div class="license-widget-inner">
						<div class="license-widget-content">
							<div class="license-widget-item-icon license-widget-item-icon--partner"></div>
							<div class="license-widget-item-content">
								${this.getTitle()}
							</div>
						</div>
						<div class="license-widget-item-icon__arrow-right ui-icon-set --arrow-right"/>
					</div>
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
						${this.getOptions().title}
					</span>
				</div>
			`;
		});
	}
}
