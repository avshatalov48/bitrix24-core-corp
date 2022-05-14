export class StatsCalculator
{
	calculatePercentage(first: number, second: number): number
	{
		if (first === 0)
		{
			return 0;
		}

		if (first === '')
		{
			return 100;
		}

		const result = Math.round(second * 100 / first);

		return (isNaN(result) ? 0 : result);
	}
}