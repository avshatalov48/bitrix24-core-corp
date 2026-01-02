import { EventEmitter } from 'main.core.events';
import { FeaturePromotersRegistry } from 'ui.info-helper';
import { Content } from './content';
import { Tag } from 'main.core';

export class MarketContent extends Content
{
	getConfig(): Object
	{
		return {
			html: this.getLayout(),
			minHeight: this.getOptions().isSmall ? '86px' : '55px',
		};
	}

	getLayout(): HTMLElement
	{
		return this.cache.remember('layout', () => {
			return Tag.render`
				<div data-id="${this.getLayoutId()}" class="license-widget-item license-widget-item--secondary ${this.getMainClass()}">
					<div class="license-widget-inner ${this.getOptions().isSmall ? '--column' : ''}">
						<div class="license-widget-content">
							${this.getIcon()}
							<div class="license-widget-item-content">
								${this.getTitle()}
								${this.getDescription()}
							</div>
						</div>
						${this.getButton()}
					</div>
				</div>
			`;
		});
	}

	getLayoutId(): string
	{
		return 'license-widget-block-market';
	}

	getTitle(): HTMLElement
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

	getIcon(): HTMLElement
	{
		return this.cache.remember('icon', () => {
			return Tag.render`
				<div class="license-widget-item-icon license-widget-item-icon--mp"/>
			`;
		});
	}

	getDescription(): HTMLElement
	{
		return this.cache.remember('description', () => {
			if (this.getOptions().isPaid || this.getOptions().isDemo)
			{
				return this.getReminderMessage();
			}

			return this.getDescriptionLink();
		});
	}

	getMainClass(): string
	{
		if (this.getOptions().isExpired || this.getOptions().isAlmostExpired)
		{
			return '--market-expired';
		}

		if (this.getOptions().isPaid || this.getOptions().isDemo)
		{
			return '--market-active';
		}

		return '--market-default';
	}

	getReminderMessage(): ?HTMLDivElement
	{
		return this.cache.remember('reminder-message', () => {
			return Tag.render`
				<div class="license-widget-item-info">
					<span class="license-widget-item-info-text">
						${this.getOptions().messages.remainder}
					</span>
				</div>
			`;
		});
	}

	getDescriptionLink(): HTMLDivElement
	{
		return this.cache.remember('description-link', () => {
			const showHelper = () => {
				FeaturePromotersRegistry.getPromoter({ code: this.getOptions().description.landingCode }).show();
			};

			return Tag.render`
				<div class="license-widget-item-link">
					<span class="license-widget-item-link-text" onclick="${showHelper}">
						${this.getOptions().description.text}
					</span>
				</div>
			`;
		});
	}

	getButton(): HTMLLinkElement
	{
		return this.cache.remember('button', () => {
			const onclick = () => {
				EventEmitter.emit(EventEmitter.GLOBAL_TARGET, 'BX.Intranet.LicenseWidget.Popup:openChild');
			};

			return Tag.render`
				<a onclick="${onclick}" href="${this.getOptions().button.link}" class="license-widget-item-btn" target="_blank">
					${this.getOptions().button.text}
				</a>
			`;
		});
	}
}