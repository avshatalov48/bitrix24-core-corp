import { Type } from 'main.core';

export function requireClassOrNull(param: any, constructor: Function, paramName: string): ?Object
{
	if (Type.isNil(param))
	{
		return param;
	}

	return requireClass(param, constructor, paramName);
}

export function requireClass(param: any, constructor: Function, paramName: string): Object
{
	if (param instanceof constructor)
	{
		return param;
	}

	throw new Error(`Expected ${paramName} be an instance of ${constructor.name}, got ${getType(param)} instead`);
}

export function requireArrayOfString(param: any, paramName: string): Array<string>
{
	if (!Type.isArray(param))
	{
		throw new TypeError(`Expected ${paramName} should be an array of strings, got ${getType(param)} instead`);
	}

	param.forEach((value, index) => {
		if (!Type.isString(value))
		{
			throw new TypeError(`Expected ${paramName} should be an array of strings, instead the element at index ${index} is ${getType(value)}`);
		}
	});

	return param;
}

export function requireStringOrNull(param: any, paramName: string): ?string
{
	if (Type.isStringFilled(param) || Type.isNil(param))
	{
		return param;
	}

	throw new Error(`Expected ${paramName} be either non-empty string or null, got ${getType(param)} instead`);
}

function getType(value: any): string
{
	if (Type.isObject(value) && !Type.isPlainObject(value))
	{
		return value?.constructor?.name || 'unknown';
	}

	// eslint-disable-next-line @bitrix24/bitrix24-rules/no-typeof
	return typeof value;
}
