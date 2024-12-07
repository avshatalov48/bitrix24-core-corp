import { Text, Type } from 'main.core';
import type { Error } from './index';

export function normalizeId(id: any): ?number
{
	if (Type.isNil(id))
	{
		return null;
	}

	return Text.toInteger(id);
}

export function normalizeDynamicTypesTitles(titles: any): Object
{
	if (!Type.isPlainObject(titles))
	{
		return {};
	}

	const result = {};
	for (const [key, value] of Object.entries(titles))
	{
		if (Text.toInteger(key) > 0 && Type.isStringFilled(value))
		{
			result[key] = value;
		}
	}

	return result;
}

export function normalizeTitle(title: any): ?string
{
	if (Type.isNil(title))
	{
		return null;
	}

	return String(title);
}

export function normalizeTypesIds(typeIds: any): number[]
{
	if (!Type.isArrayFilled(typeIds))
	{
		return [];
	}

	return typeIds.map((x) => normalizeTypeId(x)).filter((x) => x > 0);
}

export function normalizeTypeId(typeId: any): number
{
	return Text.toInteger(typeId);
}

export function normalizeErrors(errors: any): Error[]
{
	if (!Type.isArrayFilled(errors))
	{
		return [];
	}

	return errors.filter((x) => isValidError(x));
}

export function isValidError(error: any): boolean
{
	return (
		Type.isStringFilled(error.message)
		&& (
			Type.isNil(error.code)
			|| Type.isStringFilled(error.code)
			|| Type.isInteger(error.code)
		)
		&& (
			Type.isNil(error.customData)
			|| Type.isPlainObject(error.customData)
		)
	);
}
