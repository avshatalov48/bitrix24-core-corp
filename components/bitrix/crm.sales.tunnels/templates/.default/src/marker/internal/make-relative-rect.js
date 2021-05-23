import {Type} from 'main.core';

const isValidRect = rect => (
	Type.isNumber(rect.left)
	&& Type.isNumber(rect.top)
	&& Type.isNumber(rect.width)
	&& Type.isNumber(rect.height)
);

export default function makeRelativeRect(rect1: DOMRect, rect2: DOMRect): DOMRect
{
	if (!isValidRect(rect1) || !isValidRect(rect2))
	{
		throw new Error('Invalid rect. Rect should includes x, y, width and height props with a number value');
	}

	return {
		left: rect2.left - rect1.left,
		top: rect2.top - rect1.top,
		right: (rect2.left - rect1.left) + rect2.width,
		bottom: (rect2.top - rect1.top) + rect2.height,
		width: rect2.width,
		height: rect2.height,
	};
}