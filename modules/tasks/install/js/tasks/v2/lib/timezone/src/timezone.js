import { Extension } from 'main.core';

const settings = Extension.getSettings('tasks.v2.lib.timezone');

class Timezone
{
	getOffset(dateTs: number, timeZone: string = this.getTimezone()): number
	{
		return this.getTimezoneOffset(dateTs, timeZone) + new Date(dateTs).getTimezoneOffset() * 60 * 1000;
	}

	getTimezoneOffset(dateTs: number, timeZone: string = this.getTimezone()): number
	{
		const date = new Date(dateTs);
		const dateInTimezone = new Date(date.toLocaleString('en-US', { timeZone }));
		const dateInUTC = new Date(date.toLocaleString('en-US', { timeZone: 'UTC' }));

		return dateInTimezone.getTime() - dateInUTC.getTime();
	}

	getTimezone(): number
	{
		return settings.timeZone || Intl.DateTimeFormat().resolvedOptions().timeZone;
	}
}

export const timezone = new Timezone();
