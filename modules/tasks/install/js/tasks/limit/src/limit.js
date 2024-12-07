import { Runtime, Type } from 'main.core';

type Params = {
	featureId: string,
	code?: string,
	bindElement?: HTMLElement,
	limitAnalyticsLabels?: Object,
}

export class Limit
{
	static instances = {};

	#featureId: string;
	#code: string;
	#bindElement: ?HTMLElement;
	#limitAnalyticsLabels: Object = {};

	constructor(params: Params)
	{
		this.#featureId = params.featureId;
		this.#code = Type.isStringFilled(params.code) ? params.code : `limit_${this.#featureId}`;
		this.#bindElement = Type.isElementNode(params.bindElement) ? params.bindElement : null;

		if (Type.isPlainObject(params.limitAnalyticsLabels))
		{
			this.#limitAnalyticsLabels = { module: 'tasks', ...params.limitAnalyticsLabels };
		}
	}

	static showInstance(params: Params): Promise
	{
		if (!Type.isStringFilled(params.featureId))
		{
			throw new Error('BX.Tasks.Limit: featureId is required');
		}

		return (new this(params)).show();
	}

	show(): Promise
	{
		return new Promise((resolve, reject) => {
			Runtime.loadExtension('ui.info-helper')
				.then(({ FeaturePromotersRegistry }) => {
					if (FeaturePromotersRegistry)
					{
						FeaturePromotersRegistry.getPromoter({
							featureId: this.#featureId,
							code: this.#code,
							bindElement: this.#bindElement,
						}).show();
					}
					else
					{
						BX.UI.InfoHelper.show(this.#code, {
							isLimit: true,
							limitAnalyticsLabels: this.#limitAnalyticsLabels,
						});
					}

					resolve();
				})
				.catch((error) => {
					reject(error);
				})
			;
		});
	}
}
