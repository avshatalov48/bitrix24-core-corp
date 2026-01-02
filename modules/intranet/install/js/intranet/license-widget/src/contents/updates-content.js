import { Content } from './content';
import { Tag } from 'main.core';

export class UpdatesContent extends Content
{
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
				window.open(this.getOptions().link, '_blank');
			};

			return Tag.render`
				<div onclick="${onclick}" data-id="license-widget-block-whatsnew" class="license-widget-item license-widget-item--secondary --pointer">
					<div class="license-widget-inner">
						<div class="license-widget-content">
							${this.getMainIcon()}
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

	getMainIcon(): HTMLDivElement
	{
		return this.cache.remember('main-icon', () => {
			return Tag.render`
				<div class="license-widget-item-icon license-widget-item-icon--updates"></div>
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
