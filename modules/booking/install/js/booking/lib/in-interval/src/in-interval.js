opaque type Interval = [number, number];
export opaque type IncludeBoundariesValue = 'all' | 'left' | 'right' | 'none';

export opaque type InIntervalOptions = {
	include: IncludeBoundariesValue,
};

const defaultOptions: InIntervalOptions = {
	include: 'none',
};

export const IncludeBoundaries = Object.freeze({
	all: 'all',
	left: 'left',
	right: 'right',
	none: 'none',
});

export function inInterval(value: number, interval: Interval, options?: InIntervalOptions): boolean
{
	if (!Array.isArray(interval) && interval.length < 2)
	{
		throw new TypeError('"interval" must be an numeric array');
	}

	const { include } = { ...defaultOptions, ...options };
	if (include === IncludeBoundaries.all)
	{
		return interval[0] <= value && value <= interval[1];
	}

	if (include === IncludeBoundaries.left)
	{
		return interval[0] <= value && value < interval[1];
	}

	if (include === IncludeBoundaries.right)
	{
		return interval[0] < value && value <= interval[1];
	}

	return interval[0] < value && value < interval[1];
}
