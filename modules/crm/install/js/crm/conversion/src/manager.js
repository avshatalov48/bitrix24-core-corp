import type { ConverterParams } from "./converter";
import { Converter } from "./converter";
import { Config } from "./config";
import type { SchemeData } from "./scheme";
import { Scheme } from "./scheme";
import { Reflection } from "main.core";
import type { ConfigItemData } from "./config-item";

let instance = null;

/**
 * @memberOf BX.Crm.Conversion
 */
export class Manager
{
	#converters: Object<number, Converter>;

	constructor() {
		this.#converters = {};
	}

	static get Instance(): Manager
	{
		if ((window.top !== window) && Reflection.getClass('top.BX.Crm.Conversion.Manager'))
		{
			return window.top.BX.Crm.Conversion.Manager.Instance;
		}

		if (instance === null)
		{
			instance = new Manager();
		}

		return instance;
	}

	initializeConverter(
		entityTypeId: number,
		params: {
			configItems: ConfigItemData[],
			scheme: SchemeData,
			params: ConverterParams,
		}
	): Converter
	{
		const config = Config.create(entityTypeId, params.configItems, Scheme.create(params.scheme));

		this.#converters[entityTypeId] = new Converter(entityTypeId, config, params.params);

		return this.#converters[entityTypeId];
	}

	getConverter(entityTypeId: number): ?Converter
	{
		return this.#converters[entityTypeId] || null;
	}
}
