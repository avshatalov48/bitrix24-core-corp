export class Time
{
	static calculateInEntityOnADate(state, entity, date)
	{
		return state.history
			.filter(entry => (
				entry.privateCode === entity.privateCode
				&& entry.dateLog === date
			))
			.map(Time.calculateInEntry)
			.reduce((sum, time) => sum + time, 0);
	}

	static calculateInEntry(entry)
	{
		const time = entry.time
			.map(interval => {
				const finish = interval.finish ? new Date(interval.finish) : new Date();

				return finish - new Date(interval.start);
			})
			.reduce((sum, interval) => sum + interval, 0);

		return Math.round(time / 1000);
	}

	static formatDateToTime(date: Date)
	{
		const addZero = num => (num >= 0 && num <= 9) ? '0' + num : num;

		const hour = date.getHours();
		const min = addZero(date.getMinutes());

		return hour + ':' + min;
	}

	static msToSec(ms)
	{
		return ms / 1000;
	}
}