import { ajax, Extension } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { ResponseDataType } from './types/response-data-type';
import { ResponseServiceDataType } from './types/response-service-data-type';
import { Widget } from './widget';
import { Analytics } from './analytics';

export class ServiceWidget extends Widget
{
	constructor(serviceCode: string, serviceData: ?ResponseServiceDataType)
	{
		super(serviceCode, serviceData);

		const settings = Extension.getSettings('baas.store');

		if (BX.PULL && settings.pull)
		{
			BX.PULL.extendWatch(settings.pull.channelName);

			EventEmitter.subscribe('onPullEvent-baas', (event: BaseEvent) => {
				const [command: string, params: ResponseDataType] = event.getData();

				if (command === 'updateService' && params.service.code === this.serviceCode)
				{
					this.setServiceData(params.service);
				}
			});
		}
	}

	setServiceData(serviceData: ?ResponseServiceDataType): this
	{
		if (serviceData)
		{
			this.serviceData = { ...serviceData };
			this.presenter.adjustServiceData(this.serviceData);
		}

		return this;
	}

	getAjaxPromise(): Promise
	{
		return ajax.runAction('baas.Service.get', { data: { code: this.serviceCode } });
	}

	bindAnalyticContext(node: HTMLElement, bxAnalyticContextLabel: string): this
	{
		let contextLabel = bxAnalyticContextLabel;
		if (Analytics.CONTEXT_IS_NOT_SET === bxAnalyticContextLabel)
		{
			contextLabel = Analytics.guessContextByServiceCode(this.serviceCode);
		}

		super.bindAnalyticContext(node, contextLabel);

		return this;
	}

	static #instances = [];

	static getInstanceByCode(code: string): Widget
	{
		if (!this.#instances[code])
		{
			this.#instances[code] = new this(code, Extension.getSettings('baas.store').services[code]);
		}

		return this.#instances[code];
	}
}
