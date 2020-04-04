import {Type} from 'main.core';

export default function isLine(value)
{
	return (
		Type.isArray(value)
		&& value.length === 2
		&& value.every(Type.isNumber)
	);
}