import { Controller } from './base/controller';

let storedValues = null;
const lsStoredValuesKey = 'b24-form-field-stored-values';

function restore(): Object
{
	if (storedValues !== null)
	{
		return storedValues;
	}

	if (window.localStorage)
	{
		let stored = window.localStorage.getItem(lsStoredValuesKey);
		if (stored)
		{
			try
			{
				storedValues = JSON.parse(stored);
			}
			catch (e) {}
		}
	}

	storedValues = storedValues || {};
	storedValues.type = storedValues.type || {};
	storedValues.name = storedValues.name || {};

	return storedValues;
}

export function storeFieldValues(fields: Controller[]): Object
{
	try
	{
		if (!window.localStorage)
		{
			return storedValues;
		}

		const storedTypes = ['name', 'second-name', 'last-name', 'email', 'phone'];
		const stored = fields.reduce((result, field: Controller) => {
			if (storedTypes.indexOf(field.getType()) >= 0 && field.autocomplete || field.autocomplete === true)
			{
				const value = field.value();
				if (value)
				{
					if (storedTypes.indexOf(field.getType()) >= 0 && field.autocomplete)
					{
						result.type[field.getType()] = value;
					}

					result.name[field.name] = value;
				}
			}

			return result;
		}, restore());

		window.localStorage.setItem(lsStoredValuesKey, JSON.stringify(stored));
	}
	catch (e) {}
}

export function getStoredFieldValue(fieldType: string): string
{
	const storedTypes = ['name', 'second-name', 'last-name', 'email', 'phone'];
	if (storedTypes.indexOf(fieldType) < 0)
	{
		return '';
	}

	return restore()['type'][fieldType] || '';
}

export function getStoredFieldValueByFieldName(fieldName: string): string
{
	return restore()['name'][fieldName] || '';
}