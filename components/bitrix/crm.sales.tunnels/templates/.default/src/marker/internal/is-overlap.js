import isLine from './is-line';

export default function isOverlap(line1, line2)
{
	if (!isLine(line1) || !isLine(line2))
	{
		throw new Error('Invalid lines. Line should be Array<number>');
	}

	const a1 = Math.min(...line1);
	const a2 = Math.max(...line1);

	const b1 = Math.min(...line2);
	const b2 = Math.max(...line2);

	return a1 >= b1 && a1 <= b2
		|| a2 >= b1 && a2 <= b2
		|| b1 >= a1 && b1 <= a2
		|| b2 >= a1 && b2 <= a2;
}