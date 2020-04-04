import {Type} from 'main.core';

export default function isRect(value)
{
	return (
		Type.isNumber(value.left)
		&& Type.isNumber(value.top)
		&& Type.isNumber(value.width)
		&& Type.isNumber(value.height)
	);
}