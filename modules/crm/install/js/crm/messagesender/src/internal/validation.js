import { Type } from 'main.core';
import { ItemIdentifier } from 'crm.data-structures';
import { Receiver } from '../receiver';

export function ensureIsItemIdentifier(candidate: any): void
{
	if (candidate instanceof ItemIdentifier)
	{
		return;
	}

	throw new Error('Argument should be an instance of ItemIdentifier');
}

export function ensureIsReceiver(candidate: any): void
{
	if (candidate instanceof Receiver)
	{
		return;
	}

	throw new Error('Argument should be an instance of Receiver');
}

export function ensureIsValidMultifieldValue(candidate: any): void
{
	// noinspection OverlyComplexBooleanExpressionJS
	const isValidValue = (
		Type.isPlainObject(candidate)
		&& (Type.isNil(candidate.id) || Type.isInteger(candidate.id))
		&& Type.isStringFilled(candidate.typeId)
		&& Type.isStringFilled(candidate.valueType)
		&& Type.isStringFilled(candidate.value)
	);

	if (isValidValue)
	{
		return;
	}

	throw new Error('Argument should be an object of valid MultifieldValue structure');
}

export function ensureIsValidSourceData(candidate: any): void
{
	const isValid = (
		Type.isPlainObject(candidate)
		&& Type.isStringFilled(candidate.title)
	);

	if (isValid)
	{
		return;
	}

	throw new Error('Argument should be an object of valid SourceData structure')
}
