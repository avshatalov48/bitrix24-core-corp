import { Config } from './config';
import type { ConfigItemData } from './config-item';
import type { ConverterParams } from './converter';
import { Converter } from './converter';
import { EntitySelector } from './entity-selector';
import type { SchemeData } from './scheme';
import { Scheme } from './scheme';

let instance = null;

/**
 * @memberOf BX.Crm.Conversion
 */
export class Manager
{
	#converters: Object<string, Converter> = {};

	static get Instance(): Manager
	{
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
		},
	): Converter
	{
		const config = Config.create(entityTypeId, params.configItems, Scheme.create(params.scheme));

		const converter = new Converter(entityTypeId, config, params.params);

		this.#converters[converter.getId()] = converter;

		return converter;
	}

	getConverter(converterId: string): ?Converter
	{
		return this.#converters[converterId];
	}

	createEntitySelector(converterId: string, dstEntityTypeIds: number[], entityId: number): ?EntitySelector
	{
		const converter = this.getConverter(converterId);
		if (!converter)
		{
			console.error('Converter with given id not found', converterId, this);

			return null;
		}

		// check whether converter supports this type of scheme
		const schemeItem = converter.getConfig().getScheme().getItemForEntityTypeIds(dstEntityTypeIds);
		if (!schemeItem)
		{
			console.error('Could not find scheme item', dstEntityTypeIds, converter);

			return null;
		}

		return new EntitySelector(converter, entityId, dstEntityTypeIds);
	}
}
