import { Builder, Dictionary } from 'crm.integration.analytics';
import { DatetimeConverter } from 'crm.timeline.tools';
import { ajax as Ajax, Loc, Tag, Type } from 'main.core';
import 'ui.design-tokens';
import { sendData } from 'ui.analytics';
import { Footer } from '../footer';
import type { WidgetParams, WidgetTypeEnum } from '../widget';
import { WidgetType } from '../widget';
import { Base } from './base';

// @todo need refactor and merge with start.js
export class ForceStart extends Base
{
	#isFlowStarted: ?boolean = null;
	#showSettingsButton: boolean = true;
	#hasClients: boolean = false;
	#canEnableFeature: boolean = false;
	#flowExpectedEnableTimestamp: ?number = null;

	constructor(params: WidgetParams)
	{
		super(params);

		this.#showSettingsButton = params.showSettingsButton ?? true;
	}

	getType(): WidgetTypeEnum
	{
		return WidgetType.forceStart;
	}

	onClose(): void
	{
		super.onClose();

		void Ajax.runAction('crm.repeatsale.widget.incrementShowedFlowStartCount');

		if (!this.#isFlowStarted && this.#flowExpectedEnableTimestamp === null && this.#canEnableFeature)
		{
			void Ajax.runAction('crm.repeatsale.flow.saveExpectedEnableDate');
		}
	}

	getPopupContent(data: ?Object = null): HTMLElement
	{
		if (Type.isObject(data))
		{
			if (this.#isFlowStarted === null)
			{
				const { isFlowStarted, canEnableFeature, flowExpectedEnableTimestamp } = data;
				this.#isFlowStarted = isFlowStarted;
				this.#canEnableFeature = canEnableFeature ?? false;
				this.#flowExpectedEnableTimestamp = flowExpectedEnableTimestamp ?? null;
			}

			this.#hasClients = this.#isHasClients(data);
		}

		return Tag.render`
			<div>
				<header class="crm-rs__w-header">
					${this.#getTitle()}
				</header>
				${this.#getBodyContentWithClients()}
				${this.#getFooterContent()}
			</div>
		`;
	}

	#getBodyContentWithClients(): HTMLElement
	{
		return Tag.render`
			<div class="crm-rs__w-body">
				<div class="crm-rs__w-body-content">
					<div class="crm-rs__w-body-title">
						${this.#getBodyTitle()}
					</div>
					${this.#canEnableFeature ? this.#getButton() : null}
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

		return Loc.getMessage('CRM_REPEAT_SALE_WIDGET_START_POPUP_BODY_TITLE');
	}

	#getButton(): ?string
	{
		if (this.#isFlowStarted)
		{
			return null;
		}

		return Tag.render`
			<div class="crm-rs__w-body-title-btn">
				<span
					onclick="${this.#onButtonClick.bind(this)}"
				>${Loc.getMessage('CRM_REPEAT_SALE_WIDGET_START_POPUP_BTN_FORCE')}</span>
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
		const content = this.#getDescriptionContent();

		return Tag.render`
			<div class="crm-rs__w-body-description ${hasClients ? '--has-clients' : ''}">
				${hasClients ? null : '<div class="crm-rs__w-body-description-border"></div>'}
				<div class="crm-rs__w-body-description-text ${hasClients ? '--has-clients' : ''}">
					<span>${content}</span>
				</div>
				<div class="crm-rs__w-body-description-btn">
					<span
						onclick="${this.#onReadMoreButtonClick.bind(this)}"
					>${Loc.getMessage('CRM_REPEAT_SALE_WIDGET_START_POPUP_BTN_READ_MORE')}</span>
				</div>
			</div>
		`;
	}

	#getDescriptionContent(): string
	{
		let code = null;
		const replacements = {};

		let isNeedReplaceLink = false;
		let isNeedReplaceDate = false;

		if (this.#flowExpectedEnableTimestamp === null && this.#canEnableFeature)
		{
			if (this.params.isRepeatSaleGrid)
			{
				code = 'CRM_REPEAT_SALE_WIDGET_START_POPUP_DESC_WITHOUT_TIME_IN_RS_GRID';
			}
			else
			{
				code = 'CRM_REPEAT_SALE_WIDGET_START_POPUP_DESC_WITHOUT_TIME';
				isNeedReplaceLink = true;
			}

			isNeedReplaceLink = true;
		}
		else if (this.#flowExpectedEnableTimestamp === null)
		{
			code = 'CRM_REPEAT_SALE_WIDGET_START_POPUP_DESC_WITHOUT_TIME_AND_PERMISSIONS';
			isNeedReplaceDate = true;
		}
		else if (this.#canEnableFeature)
		{
			if (this.params.isRepeatSaleGrid)
			{
				code = 'CRM_REPEAT_SALE_WIDGET_START_POPUP_DESC_WITHOUT_TIME_IN_RS_GRID';
			}
			else
			{
				code = 'CRM_REPEAT_SALE_WIDGET_START_POPUP_DESC_WITH_TIME_AND_PERMISSIONS';
				isNeedReplaceLink = true;
			}

			isNeedReplaceDate = true;
		}
		else
		{
			code = 'CRM_REPEAT_SALE_WIDGET_START_POPUP_DESC_WITH_TIME';
			isNeedReplaceDate = true;
		}

		if (isNeedReplaceLink)
		{
			replacements['[link]'] = '<a class="ui-link" href="/crm/repeat-sale-segment/">';
			replacements['[/link]'] = '</a>';
		}

		if (isNeedReplaceDate)
		{
			const userTime = (DatetimeConverter.createFromServerTimestamp(this.#flowExpectedEnableTimestamp)).toUserTime();

			replacements['#DATE#'] = userTime.toDateString();
			replacements['#TIME#'] = userTime.toTimeString();
		}

		return Loc.getMessage(code, replacements);
	}

	#onButtonClick(): void
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
		return Dictionary.TYPE_REPEAT_SALE_BANNER_START_FORCE;
	}
}
