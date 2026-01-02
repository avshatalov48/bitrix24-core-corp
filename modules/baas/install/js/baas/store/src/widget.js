import { Cache, BaseCache, ajax, Event, Type } from 'main.core';
import { EventEmitter } from 'main.core.events';
import 'ui.icons.b24';
import { Popup } from 'main.popup';
import { PresenterDefault } from './presenter/presenter-default';
import { PresenterFactory } from './presenter/presenter-factory';
import { Analytics } from './analytics';
import { ResponseServiceDataType } from './types/response-service-data-type';

export class Widget extends EventEmitter
{
	cache: BaseCache = new Cache.MemoryCache();
	presenter: PresenterDefault;

	serviceCode: string;
	serviceData: ?ResponseServiceDataType;

	constructor(serviceCode: string, serviceData: ?ResponseServiceDataType)
	{
		super();

		this.setEventNamespace('BX.Baas');

		this.serviceCode = serviceCode;
		this.serviceData = serviceData;

		this.presenter = PresenterFactory.createForWidget(
			this.serviceCode,
			this.serviceData,
			this.getAjaxPromise.bind(this),
		);
		EventEmitter.subscribe(
			this.presenter,
			'BX.Baas:onClickBack',
			() => this.emit('onClickBack'),
		);

		this.cache.set('boundElements', new WeakMap());
	}

	// deprecated
	getPopup(): Popup
	{
		return this.presenter.getPopup();
	}

	getAjaxPromise(): Promise
	{
		return ajax.runAction('baas.Service.getAll');
	}

	bind(node: HTMLElement, bxAnalyticContextLabel: ?string): this
	{
		this.cache.set('boundLastElement', node);

		if (!this.cache.get('boundElements').has(node))
		{
			this.cache.get('boundElements').set(node);

			Event.bind(node, 'click', () => {
				this.show(node);
			});
		}

		this.bindAnalyticContext(node, bxAnalyticContextLabel || Analytics.CONTEXT_IS_NOT_SET);

		return this;
	}

	bindAnalyticContext(node: HTMLElement, bxAnalyticContextLabel: string): this
	{
		// eslint-disable-next-line no-param-reassign
		node.dataset.bxAnalyticContextLabel = bxAnalyticContextLabel;

		return this;
	}

	show(node: ?HTMLElement): this
	{
		const targetElement = node ?? this.cache.get('boundLastElement');

		this.presenter.show(targetElement);

		return this;
	}

	toggle(node: ?HTMLElement): this
	{
		this.presenter.toggle(node);

		return this;
	}

	hide(): this
	{
		this.presenter.hide();

		return this;
	}

	reload()
	{
		if (Type.isFunction(this.presenter.reload))
		{
			this.presenter.reload();
			this.presenter.show();
		}
	}

	static #instance = null;

	static getInstance(): Widget
	{
		if (this.#instance === null)
		{
			this.#instance = new this(
				'main',
				null,
			);
		}

		return this.#instance;
	}
}
