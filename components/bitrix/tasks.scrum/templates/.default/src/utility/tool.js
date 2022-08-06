export class Tool
{
	static escapeRegex(string: string): string
	{
		return string.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
	}
}