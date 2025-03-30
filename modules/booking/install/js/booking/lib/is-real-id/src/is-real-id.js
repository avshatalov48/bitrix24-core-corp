export function isRealId(id: string | number): boolean
{
	return Number.isInteger(id) || /^[1-9]\d*$/.test(id);
}
