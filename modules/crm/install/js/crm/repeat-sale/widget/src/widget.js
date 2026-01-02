import { ContentFactory } from './content-factory';
import { Base } from './content/base';
import 'ui.design-tokens';
import 'ui.design-tokens.air';
import './widget.css';

export type WidgetTypeEnum = WidgetType.start | WidgetType.forceStart | WidgetType.statistics;

export const PeriodType = Object.freeze({
	day30: 0,
	quarter: 1,
	halfYear: 2,
	year: 3,
});

export const WidgetType = Object.freeze({
	start: 'start',
	forceStart: 'forceStart',
	statistics: 'statistics',
});

export type WidgetParams = {
	showSettingsButton?: boolean,
	showConfetti?: boolean,
	isRepeatSaleGrid?: boolean,
	periodTypeId?: PeriodType.day30 | PeriodType.quarter | PeriodType.halfYear | PeriodType.year,
}

export class Widget
{
	static instance: Array = [];
	#contentPopupInstance: ?Base = null;

	static execute(
		widgetType: WidgetTypeEnum,
		bindElement: ?HTMLElement = null,
		params: WidgetParams = {},
		event: ?Event = null,
		onCloseCallback: ?Function = null,
	): void
	{
		if (!this.instance[widgetType])
		{
			this.instance[widgetType] = new Widget(widgetType, bindElement, params);
		}

		if (this.instance[widgetType].isShown())
		{
			this.instance[widgetType].close();
		}
		else
		{
			const forceShowConfetti = (event?.altKey && event?.ctrlKey) ?? false;

			this.instance[widgetType].show(forceShowConfetti, onCloseCallback);
		}
	}

	constructor(widgetType: WidgetTypeEnum, bindElement: ?HTMLElement = null, params: WidgetParams = {})
	{
		this.#contentPopupInstance = ContentFactory.getContentInstance(widgetType, params);

		if (bindElement)
		{
			this.#contentPopupInstance.setBindElement(bindElement);
		}
	}

	show(forceShowConfetti: boolean = false, onCloseCallback: ?Function = null): void
	{
		this.#contentPopupInstance.show(forceShowConfetti, onCloseCallback);
	}

	isShown(): boolean
	{
		return this.#contentPopupInstance.isShown();
	}

	close(): void
	{
		this.#contentPopupInstance.close();
	}
}