import { Content } from './content';
import { Tag } from 'main.core';

export class TelephonyContent extends Content
{
	constructor(options)
	{
		super(options);
		this.setEventNamespace('BX.Bitrix24.LicenseWidget.Content.Telephony');
	}

	getConfig(): Object
	{
		return {
			html: this.getLayout(),
			minHeight: '43px',
			sizeLoader: 30,
		};
	}

	getLayout(): HTMLElement
	{
		return this.cache.remember('layout', () => {
			const onclick = () => {
				document.location.href = this.getOptions().link;
			};

			return Tag.render`
				<div data-id="license-widget-block-telephony" onclick="${onclick}" class="license-widget-item license-widget-item--secondary --pointer">
					<div class="license-widget-inner">
						<div class="license-widget-content">
							<div class="license-widget-item-icon ${this.getOptions().isActive ? 'license-widget-item-icon--tel-active' : 'license-widget-item-icon--tel'}"/>
							<div class="license-widget-item-content">
								<div class="license-widget-item-name">
									${this.getOptions().title}
								</div>
							</div>
						</div>
						<div class="license-widget-item-icon__arrow-right ui-icon-set --arrow-right"/>
					</div>
				</div>
			`;
		});
	}
}
