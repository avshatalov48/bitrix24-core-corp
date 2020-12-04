export class StatsCalculator
{
	calculatePercentage(first: number, second: number): number
	{
		const result = Math.round(second * 100 / first);
		return (isNaN(result) ? 0 : result);
	}
}