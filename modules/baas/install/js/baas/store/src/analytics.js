import { Extension } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { sendData } from 'ui.analytics';

export class Analytics
{
	static EVENT_SHOW_POPUP = 'show';
	static EVENT_CLICK_TO_BUY = 'button_buy_click';
	static EVENT_CLICK_TO_VIEW_PURCHASE = 'button_click_active_boost';

	static SERVICE_ALL = 'main';
	static SERVICE_AI_COPILOT_TOKEN = 'copilot';
	static SERVICE_DOCUMENTGENERATOR_FAST_TRANSFORM = 'speed';
	static SERVICE_DISK_OO_EDIT = 'docs';

	static CONTEXT_IS_NOT_SET = 'notSet';
	static CONTEXT_LICENSE_WIDGET = 'headerPopup';
	static CONTEXT_IM_CHAT = 'chat';
	static CONTEXT_STREAM = 'stream';
	static CONTEXT_CRM = 'CRM';
	static CONTEXT_TASKS = 'tasks';

	#serviceCode: string;
	#lastContext: string;
	#active: boolean = false;

	constructor(serviceCode: string)
	{
		this.#serviceCode = serviceCode;
		this.#lastContext = Analytics.CONTEXT_IS_NOT_SET;
		this.onClickToBuyPackage = this.onClickToBuyPackage.bind(this);
		this.onPurchaseShown = this.onPurchaseShown.bind(this);
	}

	activate(): this
	{
		this.#active = true;
		EventEmitter.subscribe('BX.Baas:onClickToBuyPackage', this.onClickToBuyPackage);
		EventEmitter.subscribe('BX.Baas:onPurchaseShown', this.onPurchaseShown);

		return this;
	}

	deactivate(): this
	{
		EventEmitter.unsubscribe('BX.Baas:onClickToBuyPackage', this.onClickToBuyPackage);
		EventEmitter.unsubscribe('BX.Baas:onPurchaseShown', this.onPurchaseShown);
		this.#active = false;

		return this;
	}

	onShowFrom(context: string)
	{
		this.#lastContext = context;
		this.constructor.#send(this.constructor.EVENT_SHOW_POPUP, this.#serviceCode, context);
	}

	onPlayerEvents(eventName: string, additionalVideoParameter: string)
	{
		if (this.#active)
		{
			this.constructor.#send(
				eventName,
				this.#serviceCode,
				this.#lastContext,
				additionalVideoParameter ? { p2: additionalVideoParameter } : {},
			);
		}
	}

	onClickToBuyPackage(event: BaseEvent)
	{
		this.constructor.#send(
			this.constructor.EVENT_CLICK_TO_BUY,
			this.#serviceCode,
			this.#lastContext,
			{ p2: event.getData().packageCode.replaceAll('_', '-') },
		);
	}

	onPurchaseShown(event: BaseEvent)
	{
		this.constructor.#send(
			this.constructor.EVENT_CLICK_TO_VIEW_PURCHASE,
			this.#serviceCode,
			this.#lastContext,
			{ p2: event.getData().packageCode.replaceAll('_', '-') },
		);
	}

	static #send(event: string, serviceCode: string, context: string, additional: Object = {})
	{
		sendData(Object.assign(additional, {
			tool: 'boost',
			category: 'buy',
			event,
			type: serviceCode,
			c_section: context,
			p1: Extension.getSettings('baas.store').get('isCurrentUserAdmin') ? 'isAdmin_Y' : 'isAdmin_N',
		}));
	}

	static createByServiceCode(serviceCodeId: string): this
	{
		let serviceCode = null;
		switch (serviceCodeId)
		{
			case 'ai_copilot_token':
				serviceCode = this.SERVICE_AI_COPILOT_TOKEN;
				break;
			case 'disk_oo_edit':
				serviceCode = this.SERVICE_DISK_OO_EDIT;
				break;
			case 'documentgenerator_fast_transform':
				serviceCode = this.SERVICE_DOCUMENTGENERATOR_FAST_TRANSFORM;
				break;
			default:
				serviceCode = this.SERVICE_ALL;
				break;
		}

		return new this(serviceCode);
	}

	static guessContextByServiceCode(serviceCodeId: string): string
	{
		let serviceContext = null;
		switch (serviceCodeId)
		{
			case 'ai_copilot_token':
				serviceContext = this.CONTEXT_STREAM;
				break;
			case 'disk_oo_edit':
				serviceContext = this.CONTEXT_TASKS;
				break;
			case 'documentgenerator_fast_transform':
				serviceContext = this.CONTEXT_CRM;
				break;
			default:
				serviceContext = this.CONTEXT_LICENSE_WIDGET;
				break;
		}

		return serviceContext;
	}
}
