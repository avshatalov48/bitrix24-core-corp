import { Tag, Runtime, Extension } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { FeaturePromotersRegistry } from 'ui.info-helper';
import { MarketContent } from './market-content';

const baasWidgetMarker = Symbol('baas widget');
export class BaasContent extends MarketContent
{
	getConfig(): Object
	{
		return {
			html: this.getLayout(),
			minHeight: this.getOptions().isSmall ? '86px' : '55px',
		};
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

	getDescription(): HTMLElement
	{
		if (this.getOptions().isActive)
		{
			return this.getReminderMessage();
		}

		return this.getDescriptionLink();
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

	getReminderMessage(): ?HTMLDivElement
	{
		return this.cache.remember('reminder-message', () => {
			const node = Tag.render`
				<div class="license-widget-item-link" onclick="${this.#showBaasWidget.bind(this)}">
					<span class="license-widget-item-link-text --active">
						${this.getOptions().messages.remainder}
					</span>
				</div>
			`;

			if (BX.PULL && Extension.getSettings('baas.store').pull)
			{
				BX.PULL.extendWatch(Extension.getSettings('baas.store').pull.channelName);

				EventEmitter.subscribe('onPullEvent-baas', (event: BaseEvent) => {
					const [command: string, params] = event.getData();
					if (command === 'updateService' && params.purchaseCount)
					{
						node.querySelector('[data-bx-role="purchaseCount"]').innerText = params.purchaseCount;
					}
				});
			}

			return node;
		});
	}

	getLayoutId(): string
	{
		return 'license-widget-block-baas';
	}

	getButton(): HTMLLinkElement
	{
		return Tag.render`
			<a class="license-widget-item-btn" onclick="${this.#showBaasWidget.bind(this)}">
				${this.getOptions().button.text}
			</a>
		`;
	}

	#showBaasWidget(): void
	{
		Runtime.loadExtension(['baas.store']).then((exports) => {
			const widget = exports.Widget.getInstance();
			if (!widget[baasWidgetMarker])
			{
				widget.subscribe('onClickBack', () => {
					this.getOptions().licensePopup.show();
				});
			}
			widget.bind(this.getOptions().licensePopupTarget, exports.Analytics.CONTEXT_LICENSE_WIDGET).show();
			EventEmitter.emit(EventEmitter.GLOBAL_TARGET, 'BX.Intranet.LicenseWidget.Popup:openChild');
		});
	}

	getIcon(): HTMLElement
	{
		return this.cache.remember('icon', () => {
			return Tag.render`
				<div class="license-widget-item-icon license-widget-item-icon--baas"/>
			`;
		});
	}

	getMainClass(): string
	{
		if (this.getOptions().isActive)
		{
			return '--baas-active';
		}

		return '--baas-default';
	}
}
