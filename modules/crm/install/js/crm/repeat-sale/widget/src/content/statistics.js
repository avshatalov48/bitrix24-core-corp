import { Builder, Dictionary } from 'crm.integration.analytics';
import 'ui.design-tokens';
import { ajax as Ajax, Loc, Reflection, Tag } from 'main.core';
import { PopupManager } from 'main.popup';
import { sendData } from 'ui.analytics';
import { Lottie } from 'ui.lottie';
import { Footer } from '../footer';
import type { WidgetParams, WidgetTypeEnum } from '../widget';
import { PeriodType, WidgetType } from '../widget';
import { Base } from './base';

import 'ui.hint';

const UserOptions = Reflection.namespace('BX.userOptions');

export class Statistics extends Base
{
	#periodType: number = PeriodType.day30;
	#showSettingsButton: boolean = true;
	#hint: ?Manager = null;

	constructor(params: WidgetParams)
	{
		super(params);

		this.#showSettingsButton = params.showSettingsButton ?? true;
		this.#periodType = params.periodTypeId ?? PeriodType.day30;
	}

	getType(): WidgetTypeEnum
	{
		return WidgetType.statistics;
	}

	getPopupWidth(): number
	{
		return 489;
	}

	isAutoHidePopup(): boolean
	{
		return true;
	}

	#getLoadingPopupContent(): HTMLElement
	{
		return Tag.render`
			<div>
				<header class="crm-rs__w-header --statistics">
					${Loc.getMessage('CRM_REPEAT_SALE_WIDGET_STATISTICS_POPUP_TITLE')}
				</header>
				<div class="crm-rs__w-body">
					<div class="crm-rs__w-body-loading-bubble">
						<div class="crm-rs__w-body-loading-bubble-wrapper">
							${this.renderLottieAnimation()}
							${this.#renderLoadingLottieAnimation()}
						</div>
						<div class="crm-rs__w-body-bubble-subtitle">
							${Loc.getMessage('CRM_REPEAT_SALE_WIDGET_STATISTICS_POPUP_LOADING')}
						</div>
					</div>
				</div>
				<footer class="crm-rs__w-footer --statistics">
					${this.#getFooterContent()}
				</footer>
			</div>
		`;
	}

	#renderLoadingLottieAnimation(): HTMLElement
	{
		const container = Tag.render`
			<div class="crm-rs__w-loading-lottie-container">
				<div ref="lottie" class="crm-rs__w-lottie"></div>
			</div>
		`;

		const mainAnimation = Lottie.loadAnimation({
			path: '/bitrix/js/crm/repeat-sale/widget/lottie/loading.json',
			container: container.lottie,
			renderer: 'svg',
			loop: true,
			autoplay: true,
		});

		mainAnimation.setSpeed(0.75);

		return container.root;
	}

	getPopupContent(data: ?Object = null): HTMLElement
	{
		return Tag.render`
			<div>
				<header class="crm-rs__w-header --statistics">
					${this.#getPopupTitle(data)}
				</header>
				<div class="crm-rs__w-body">
					<div class="crm-rs__w-body-content --statistics">
						<div class="crm-rs__w-body-statistics-table-container">
							<table class="crm-rs__w-body-statistics-table">
								<thead>
									<tr>
										<th></th>
										<th>${Loc.getMessage('CRM_REPEAT_SALE_WIDGET_STATISTICS_POPUP_COUNT')}</th>
										<th>${Loc.getMessage('CRM_REPEAT_SALE_WIDGET_STATISTICS_POPUP_SUM')}</th>
									</tr>
								</thead>
								<tbody class="crm-rs__w-body-statistics-table-body">
									<tr>
										<td><span>${Loc.getMessage('CRM_REPEAT_SALE_WIDGET_STATISTICS_POPUP_DEALS_IN_WORK')}</span></td>
										<td><span>${data.repeatSaleProcessCount ?? 0}</span></td>
										<td><span>${data.repeatSaleProcessSum ?? 0}</span></td>
									</tr>
									<tr>
										<td>${Loc.getMessage('CRM_REPEAT_SALE_WIDGET_STATISTICS_POPUP_WIN_DEALS')}</td>
										<td>${data.repeatSaleWinCount ?? 0}</td>
										<td>${data.repeatSaleWinSum ?? 0}</td>
									</tr>
								</tbody>
								<tfoot class="crm-rs__w-body-statistics-table-footer">
									<tr>
										<td>
											<div>
												${Loc.getMessage('CRM_REPEAT_SALE_WIDGET_STATISTICS_POPUP_CONVERSION')}
												<span 
													class="crm-rs__w-body-statistics-hint"
													onmouseenter="${this.#showHint.bind(this)}"
													onmouseleave="${this.#hideHint.bind(this)}"
												></span>
											</div>
										</td>
										<td><span>${data.conversionByCount}${data.conversionByCount > 0 ? '%' : ''}</span></td>
										<td>${data.conversionBySum}${data.conversionBySum > 0 ? '%' : ''}</td>
									</tr>
								</tfoot>
							</table>
						</div>
					</div>
				</div>
				<footer class="crm-rs__w-footer --statistics">
					${this.#getFooterContent()}
				</footer>
			</div>
		`;
	}

	#getPopupTitle(data: Object): HTMLElement
	{
		const repeatSaleForPeriodText = Tag.render`
			<span>
				${Loc.getMessagePlural(
					'CRM_REPEAT_SALE_WIDGET_STATISTICS_POPUP_TITLE_TOTAL_DEALS',
					data.repeatSaleTotalCount ?? 0,
					{
						'#COUNT#': data.repeatSaleTotalCount ?? 0,
					},
				)}
			</span>
		`;

		const repeatSaleTodayCount = data.repeatSaleTodayCount ?? 0;

		let repeatSaleTodayText = null;
		if (repeatSaleTodayCount > 0)
		{
			repeatSaleTodayText = Tag.render`
				<span>
					${Loc.getMessagePlural(
						'CRM_REPEAT_SALE_WIDGET_STATISTICS_POPUP_TITLE_TODAY_DEALS',
						data.repeatSaleTodayCount ?? 0,
						{
							'#COUNT#': data.repeatSaleTodayCount ?? 0,
						},
					)}
				</span>
			`;
		}
		else
		{
			const todayNoDealsMessage = Loc.getMessage('CRM_REPEAT_SALE_WIDGET_STATISTICS_POPUP_TITLE_TODAY_NO_DEALS');
			if (todayNoDealsMessage)
			{
				repeatSaleTodayText = Tag.render`<span>${todayNoDealsMessage}</span>`;
			}
		}

		return Tag.render`
			<div>
				${Loc.getMessage('CRM_REPEAT_SALE_WIDGET_STATISTICS_POPUP_TITLE')}
				<div class="crm-rs__w-header-span-wrapper">
					${repeatSaleForPeriodText}
					${repeatSaleTodayText}
				</div>
			</div>
			<div 
				class="crm-rs__w-period-selector"
				onclick="${this.#onPeriodChange.bind(this, this.#periodType)}"
			>
				${this.#getSelectorTitle()}
				<span class="crm-rs__w-period-selector-icon"></span>
			</div>
		`;
	}

	#getSelectorTitle(): string
	{
		let code = null;

		const periodType = Number(this.#periodType);

		switch (periodType)
		{
			case PeriodType.day30:
				code = 'CRM_REPEAT_SALE_WIDGET_STATISTICS_POPUP_PERIOD_DAY_30';
				break;
			case PeriodType.quarter:
				code = 'CRM_REPEAT_SALE_WIDGET_STATISTICS_POPUP_PERIOD_QUARTER';
				break;
			case PeriodType.halfYear:
				code = 'CRM_REPEAT_SALE_WIDGET_STATISTICS_POPUP_PERIOD_HALF_YEAR';
				break;
			case PeriodType.year:
				code = 'CRM_REPEAT_SALE_WIDGET_STATISTICS_POPUP_PERIOD_YEAR';
				break;
			default:
				throw new RangeError(`Unknown period type: ${periodType}`);
		}

		return Loc.getMessage(code);
	}

	#getFooterContent(): HTMLElement
	{
		const footer = new Footer(this.#showSettingsButton, {
			type: this.getAnalyticsType(),
			subSection: this.getAnalyticsSubSection(),
		});

		return footer.getFooterContent();
	}

	getAnalyticsType(): string
	{
		return Dictionary.TYPE_REPEAT_SALE_BANNER_STATISTICS;
	}

	getFetchUrl(): string
	{
		return 'crm.repeatsale.statistics.getData';
	}

	getFetchParams(): Object
	{
		return {
			periodType: this.#periodType,
		};
	}

	#onPeriodChange(periodTypeId: number): void
	{
		let nextPeriodTypeId = PeriodType.day30;

		const periodTypeIds = Object.values(PeriodType);

		if (periodTypeIds.includes(periodTypeId))
		{
			const index = periodTypeIds.indexOf(periodTypeId);
			if (index + 1 < periodTypeIds.length)
			{
				nextPeriodTypeId = index + 1;
			}
		}

		this.#savePeriodTypeId(nextPeriodTypeId);

		const data = {
			periodTypeId: nextPeriodTypeId,
		};

		const eventBuilder = this.#getClickEventBuilder();
		eventBuilder.setElement('change_period');
		eventBuilder.setPeriod(nextPeriodTypeId);
		sendData(eventBuilder.buildData());

		// @todo maybe pointless loader
		// const popup = PopupManager.getPopupById(`crm_repeat_sale_widget_${this.getType()}`);
		// if (popup)
		// {
		// 	popup.setContent(this.#getLoadingPopupContent());
		// }

		Ajax
			.runAction(this.getFetchUrl(), { data })
			.then(
				(response) => {
					if (response.status === 'success')
					{
						const popup = PopupManager.getPopupById(`crm_repeat_sale_widget_${this.getType()}`);
						if (popup === null)
						{
							return;
						}

						this.data = response.data;
						this.#periodType = nextPeriodTypeId;

						popup.setContent(this.getPopupContent(this.data));

						return;
					}

					this.showError();
				},
				(response) => {
					//popup.setContent(this.getPopupContent(this.data));
					this.showError();
				},
			)
			.catch((response) => {
				//popup.setContent(this.getPopupContent(this.data));
				this.showError();
			})
		;
	}

	#savePeriodTypeId(periodTypeId: PeriodType.day30 | PeriodType.quarter | PeriodType.halfYear | PeriodType.year): void
	{
		UserOptions.save('crm', 'repeat-sale', 'statistics-period-type-id', periodTypeId);
	}

	#showHint(event: Event): void
	{
		if (this.#getHintInstance().popup?.isShown())
		{
			return;
		}

		this.#getHintInstance().show(
			event.target,
			Loc.getMessage('CRM_REPEAT_SALE_WIDGET_STATISTICS_POPUP_CONVERSION_HINT'),
			true,
		);
	}

	#hideHint(): void
	{
		if (this.#getHintInstance().popup?.isShown())
		{
			this.#getHintInstance().hide();
		}
	}

	#getHintInstance(): Manager
	{
		if (this.#hint === null)
		{
			this.#hint = BX.UI.Hint.createInstance({
				popupParameters: {
					autoHide: true,
					events: {
						onFirstShow: () => {
							this.#hint.popup.setOffset({ offsetLeft: 9 });
						},
					},
				},
			});
		}

		return this.#hint;
	}

	#getClickEventBuilder(): Builder.RepeatSale.Banner.ClickEvent
	{
		const type = this.getAnalyticsType();
		const subSection = this.getAnalyticsSubSection();

		return Builder.RepeatSale.Banner.ClickEvent.createDefault(type, subSection);
	}
}
