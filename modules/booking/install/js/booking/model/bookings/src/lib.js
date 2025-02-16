export function dateToTsRange(dateTs: number): [number, number]
{
	const dateFrom = dateTs;
	const dateTo = new Date(dateTs).setDate(new Date(dateTs).getDate() + 1);

	return [dateFrom, dateTo];
}
