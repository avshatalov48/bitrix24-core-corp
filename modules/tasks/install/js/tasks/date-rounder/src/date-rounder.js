const SECONDS_IN_DAY = 86400;

export class DateRounder
{
	static roundToDays(offsetInSeconds: number): number
	{
		const offsetInDays = Math.max(0, offsetInSeconds / SECONDS_IN_DAY);

		return Math.ceil(offsetInDays);
	}
}
