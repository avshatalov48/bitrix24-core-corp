import { Builder, Dictionary } from 'crm.integration.analytics';
import { ajax as Ajax, Loc, Tag, Type } from 'main.core';
import 'ui.design-tokens';
import { sendData } from 'ui.analytics';
import { Footer } from '../footer';
import type { WidgetParams, WidgetTypeEnum } from '../widget';
import { WidgetType } from '../widget';
import { Base } from './base';

export class Start extends Base
{
	#isFlowStarted: ?boolean = null;
	#showSettingsButton: boolean = true;
	#hasClients: boolean = false;

	constructor(params: WidgetParams)
	{
		super(params);

		this.#showSettingsButton = params.showSettingsButton ?? true;
	}

	getType(): WidgetTypeEnum
	{
		return WidgetType.start;
	}

	onClose(): void
	{
		super.onClose();

		void Ajax.runAction('crm.repeatsale.widget.incrementShowedFlowStartCount');
	}

	getPopupContent(data: ?Object = null): HTMLElement
	{
		if (Type.isObject(data))
		{
			if (this.#isFlowStarted === null)
			{
				const { isFlowStarted } = data;
				this.#isFlowStarted = isFlowStarted;
			}

			this.#hasClients = this.#isHasClients(data);
		}

		return Tag.render`
			<div>
				<header class="crm-rs__w-header">
					${this.#getTitle()}
				</header>
				${this.#hasClients ? this.#getBodyContentWithClients() : this.#getBodyContent()}
				${this.#hasClients ? this.#getFooterContent() : null}
			</div>
		`;
	}

	#getBodyContent(): HTMLElement
	{
		return Tag.render`
			<div class="crm-rs__w-body">
				<div class="crm-rs__w-body-content">
					<div class="crm-rs__w-body-title">
						${this.#getBodyTitle()}
					</div>
					${this.#getDescription()}
				</div>
				${this.#getBubble()}
			</div>
		`;
	}

	#getBodyContentWithClients(): HTMLElement
	{
		return Tag.render`
			<div class="crm-rs__w-body">
				<div class="crm-rs__w-body-content --has-clients">
					<div class="crm-rs__w-body-title">
						${this.#getBodyTitle()}
					</div>
					${this.#getButton()}
				</div>
				${this.#getBubble()}
			</div>
		`;
	}

	#getBubble(): HTMLElement
	{
		const hasClients = this.#hasClients;

		return Tag.render`
			<div class="crm-rs__w-body-bubble ${this.#isFlowStarted ? '--flow-started' : ''} ${hasClients ? '--has-clients' : ''}">
				${this.renderLottieAnimation()}
				<div class="crm-rs__w-body-icon"></div>
			</div>
		`;
	}

	#getFooterContent(): HTMLElement
	{
		return Tag.render`
			<footer class="crm-rs__w-footer">
				${this.#getDescription()}
			</footer>
		`;
	}

	#getTitle(): string | HTMLElement
	{
		if (this.#isFlowStarted)
		{
			return Tag.render`
				<span>${Loc.getMessage('CRM_REPEAT_SALE_WIDGET_START_FLOW_STARTED_POPUP_TITLE')}</span>
			`;
		}

		return Loc.getMessage('CRM_REPEAT_SALE_WIDGET_START_POPUP_TITLE');
	}

	#getBodyTitle(): string | HTMLElement
	{
		if (this.#isFlowStarted)
		{
			return Tag.render`
				<span>${Loc.getMessage('CRM_REPEAT_SALE_WIDGET_START_POPUP_BODY_FLOW_STARTED_TITLE')}</span>
			`;
		}

		const code = (
			this.#hasClients
				? 'CRM_REPEAT_SALE_WIDGET_START_POPUP_BODY_TITLE_WITH_CLIENTS'
				: 'CRM_REPEAT_SALE_WIDGET_START_POPUP_BODY_TITLE_WITHOUT_CLIENTS'
		);

		return Loc.getMessage(code);
	}

	#getButton(): ?string
	{
		if (this.#isFlowStarted)
		{
			return null;
		}

		return Tag.render`
			<div class="crm-rs__w-body-title-btn --has-clients">
				<span
					onclick="${this.#onButtonClick.bind(this)}"
				>${Loc.getMessage('CRM_REPEAT_SALE_WIDGET_START_POPUP_BTN')}</span>
			</div>
		`;
	}

	#getDescription(): HTMLElement
	{
		if (this.#isFlowStarted)
		{
			const footer = new Footer(this.#showSettingsButton, {
				type: this.getAnalyticsType(),
				subSection: this.getAnalyticsSubSection(),
			});

			return Tag.render`
				<div class="crm-rs__w-buttons-wrapper">
					${footer.getFooterContent()}
				</div>
			`;
		}

		const hasClients = this.#hasClients;
		const code = (
			hasClients
				? 'CRM_REPEAT_SALE_WIDGET_START_POPUP_DESC_WITH_CLIENTS'
				: 'CRM_REPEAT_SALE_WIDGET_START_POPUP_DESC_WITHOUT_CLIENTS'
		);

		const content = Loc.getMessage(code);

		return Tag.render`
			<div class="crm-rs__w-body-description ${hasClients ? '--has-clients' : ''}">
				${hasClients ? null : '<div class="crm-rs__w-body-description-border"></div>'}
				<div class="crm-rs__w-body-description-text ${hasClients ? '--has-clients' : ''}">
					${content}
				</div>
				<div class="crm-rs__w-body-description-btn">
					<span
						onclick="${this.#onReadMoreButtonClick.bind(this)}"
					>${Loc.getMessage('CRM_REPEAT_SALE_WIDGET_START_POPUP_BTN_READ_MORE')}</span>
				</div>
			</div>
		`;
	}

	#onButtonClick(): void
	{
		if (this.#hasClients)
		{
			Ajax
				.runAction('crm.repeatsale.flow.enable')
				.then(
					(response) => {
						if (response.status === 'success')
						{
							this.#isFlowStarted = true;
							this.setPopupContent(this.getPopupContent());

							const instance = this.#getClickEventBuilder();
							instance.setElement('start_flow');
							sendData(instance.buildData());

							return;
						}

						this.showError();
						this.close();
					},
					(response) => {
						this.showError();
						this.close();
					},
				)
				.catch((response) => {
					this.showError();
					this.close();
				})
			;
		}
		else
		{
			this.#showReadMore();
		}
	}

	#onReadMoreButtonClick(): void
	{
		const instance = this.#getClickEventBuilder();
		instance.setElement('info_button');
		sendData(instance.buildData());

		this.#showReadMore();
	}

	#showReadMore(): void
	{
		top.BX?.Helper?.show('redirect=detail&code=25376986');
	}

	#isHasClients(data: Object): boolean
	{
		return data.count > 0;
	}

	getFetchUrl(): string
	{
		return 'crm.repeatsale.start.getData';
	}

	getFetchParams(): Object
	{
		return {};
	}

	#getClickEventBuilder(): Builder.RepeatSale.Banner.ClickEvent
	{
		const type = this.getAnalyticsType();
		const subSection = this.getAnalyticsSubSection();

		return Builder.RepeatSale.Banner.ClickEvent.createDefault(type, subSection);
	}

	onFirstShow(): void
	{
		const type = this.getAnalyticsType();
		const subSection = this.getAnalyticsSubSection();

		this.#sendShowAnalytics(type, subSection);
	}

	#sendShowAnalytics(type: string, subSection: string): void
	{
		const instance = Builder.RepeatSale.Banner.ViewEvent.createDefault(type, subSection);
		sendData(instance.buildData());
	}

	getAnalyticsType(): string
	{
		return (
			this.#hasClients
				? Dictionary.TYPE_REPEAT_SALE_BANNER_START_EMPTY
				: Dictionary.TYPE_REPEAT_SALE_BANNER_START
		);
	}
}
