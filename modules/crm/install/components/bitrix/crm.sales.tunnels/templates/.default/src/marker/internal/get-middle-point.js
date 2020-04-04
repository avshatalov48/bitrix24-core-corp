import isRect from './is-rect';

export default function getMiddlePoint(rect: DOMRect): {middleX: number, middleY: number}
{
	if (!isRect(rect))
	{
		throw new Error('Invalid rect. Rect should includes x, y, width and height props with a number value');
	}

	return {
		middleX: rect.left + (rect.width / 2),
		middleY: rect.top + (rect.height / 2),
	}
}