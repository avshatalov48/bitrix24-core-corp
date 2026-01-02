export class Timezone
{
	static #cache: { [key: string]: number } = {};

	static getOffset(dateTs: number, timeZone: string): number
	{
		const key = `${dateTs}-${timeZone}`;
		if (!this.#cache[key])
		{
			const date = new Date(dateTs);
			const dateInTimezone = new Date(date.toLocaleString('en-US', { timeZone }));
			const dateInUTC = new Date(date.toLocaleString('en-US', { timeZone: 'UTC' }));

			this.#cache[key] = (dateInTimezone.getTime() - dateInUTC.getTime()) / 1000;
		}

		return this.#cache[key];
	}
}
