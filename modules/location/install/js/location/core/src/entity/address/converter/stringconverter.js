import {Type, Text} from 'main.core';
import Address from '../../address';
import Format from '../../format';
import AddressType from '../addresstype';

export default class StringConverter
{
	static STRATEGY_TYPE_TEMPLATE = 'template';
	static STRATEGY_TYPE_FIELD_SORT = 'field_sort';
	static STRATEGY_TYPE_FIELD_TYPE = 'field_type';

	static CONTENT_TYPE_HTML = 'html';
	static CONTENT_TYPE_TEXT = 'text';
	/**
	 * Convert address to string
	 * @param {Address} address
	 * @param {Format} format
	 * @param {string} strategyType
	 * @param {string} contentType
	 * @returns {string}
	 */
	static convertAddressToString(address: Address, format: Format, strategyType: string, contentType: string): string
	{
		let result;

		if(strategyType === StringConverter.STRATEGY_TYPE_TEMPLATE)
		{
			result = StringConverter.convertAddressToStringTemplate(address, format, contentType);
		}
		else if(strategyType === StringConverter.STRATEGY_TYPE_FIELD_SORT)
		{
			const fieldSorter = (a, b) => { return a.sort - b.sort; };
			result = StringConverter.convertAddressToStringByField(address, format, fieldSorter, contentType);
		}
		else if(strategyType === StringConverter.STRATEGY_TYPE_FIELD_TYPE)
		{
			const fieldSorter = (a, b) => {

				let sortResult;

				// We suggest that UNKNOWN must be the last
				if(a.type === 0)
				{
					sortResult = 1;
				}
				else if(b.type === 0)
				{
					sortResult = -1;
				}
				else
				{
					sortResult = a.type - b.type;
				}

				return sortResult;
			};

			result = StringConverter.convertAddressToStringByField(address, format, fieldSorter, contentType);
		}
		else
		{
			throw TypeError('Wrong strategyType');
		}

		return result;
	}

	/**
	 * Convert address to string
	 * @param {Address} address
	 * @param {Format} format
	 * @param {string} contentType
	 * @returns {string}
	 */
	static convertAddressToStringTemplate(address: Address, format: Format, contentType: string): string
	{
		let result = format.template;

		if(contentType === StringConverter.CONTENT_TYPE_HTML)
		{
			result = result.replace(/\n/g, '<br/>');
		}

		const components = result.match(/{{[^]*?}}/gm);

		if(!Type.isArray(components))
		{
			return '';
		}

		// find placeholders witch looks like {{ ... }}
		for(const component of components)
		{
			// find placeholders wich looks like # ... #
			const fields = component.match(/#([0-9A-Z_]*?)#/);

			if(!Type.isArray(fields) || Type.isUndefined(fields[1]))
			{
				continue;
			}

			if(Type.isUndefined(AddressType[fields[1]]))
			{
				continue;
			}

			const type = AddressType[fields[1]];
			let fieldValue = address.getFieldValue(type);

			if(fieldValue === null)
			{
				continue;
			}

			if(contentType === StringConverter.CONTENT_TYPE_HTML)
			{
				fieldValue = Text.encode(fieldValue);
			}

			let componentReplacer = component.replace(fields[0], fieldValue);
			componentReplacer = componentReplacer.replace('{{', '');
			componentReplacer = componentReplacer.replace('}}', '');
			result = result.replace(component, componentReplacer);
		}

		result = result.replace(/({{[^]*?}})/gm, '');

		if(contentType === StringConverter.CONTENT_TYPE_HTML)
		{
			result = result.replace(/(<br\/>)+/g, '<br/>');
		}
		else
		{
			result = result.replace(/(\n)+/g, '\n');
		}

		return result;
	}

	/**
	 * Convert address to string
	 * @param {Address} address
	 * @param {Format} format
	 * @param {Function} fieldSorter
	 * @param {string} contentType
	 * @returns {string}
	 */
	static convertAddressToStringByField(address: Address, format: Format, fieldSorter: Function, contentType: string): string
	{
		if(!(format instanceof Format))
		{
			BX.debug('format must be instance of Format');
		}

		if(!(address instanceof Address))
		{
			BX.debug('address must be instance of Address');
		}

		const fieldCollection = format.fieldCollection;

		if(!fieldCollection)
		{
			return '';
		}

		const fields = Object.values(fieldCollection.fields);

		// todo: make only once or cache?
		fields.sort(fieldSorter);

		let result = '';

		for(const field of fields)
		{
			let value = address.getFieldValue(field.type);

			if(value === null)
			{
				continue;
			}

			if(contentType === StringConverter.CONTENT_TYPE_HTML)
			{
				value = Text.encode(value);
			}

			if(result !== '')
			{
				result += format.delimiter;
			}

			result += value;
		}

		return result;
	}
}