import { DateTimeFormat, Timezone } from 'main.date';

declare type DateTimeFormatOptions = {
	withDayOfWeek?: Boolean,
	withFullMonth?: Boolean,
	delimiter?: string,
};

declare type DateFormatOptions = {
	withFullMonth?: Boolean,
};

export default class DatetimeConverter
{
	#timeFormat: string;
	#dateFormat: string;
	#shortDateFormat: string;
	#longDateFormat: string;
	#mediumDateFormat: string;
	#datetime: ?Date = null; // date object which absolute time will be the same as if it was in server timezone

	/**
	 * @param timestamp Normal UTC timestamp, as it should be
	 */
	static createFromServerTimestamp(timestamp: Number): DatetimeConverter
	{
		return new DatetimeConverter(Timezone.ServerTime.getDate(timestamp));
	}

	constructor(datetime: Date)
	{
		this.#timeFormat = DateTimeFormat.getFormat('SHORT_TIME_FORMAT');
		this.#dateFormat = DateTimeFormat.getFormat('DAY_MONTH_FORMAT');
		this.#shortDateFormat = DateTimeFormat.getFormat('DAY_SHORT_MONTH_FORMAT');
		this.#longDateFormat = DateTimeFormat.getFormat('LONG_DATE_FORMAT');
		this.#mediumDateFormat = DateTimeFormat.getFormat('MEDIUM_DATE_FORMAT');

		this.#datetime = datetime;
	}

	getValue(): Date
	{
		return this.#datetime;
	}

	toUserTime(): DatetimeConverter
	{
		this.#datetime = Timezone.ServerTime.toUserDate(this.#datetime);

		return this;
	}

	toDatetimeString(options: DateTimeFormatOptions = {}): string
	{
		// eslint-disable-next-line no-param-reassign
		options = options || {};

		const now = new Date();
		const withDayOfWeek = Boolean(options.withDayOfWeek);
		const withFullMonth = Boolean(options.withFullMonth ?? true);
		const delimiter = options.delimiter || ' ';

		const showYear = this.#isShowYear();

		return DateTimeFormat.format(
			[
				['today', `today${delimiter}${this.#timeFormat}`],
				['tommorow', `tommorow${delimiter}${this.#timeFormat}`],
				['yesterday', `yesterday${delimiter}${this.#timeFormat}`],
				[
					'',
					(withDayOfWeek ? `D${delimiter}` : '')
					+ this.#getDateFormat(withFullMonth, showYear)
					+ delimiter
					+ this.#timeFormat,
				],
			],
			this.#datetime,
			now,
		).replaceAll('\\', '');
	}

	#getDateFormat(withFullMonth: boolean = false, withYear: boolean = false): string
	{
		if (withYear)
		{
			return withFullMonth ? this.#longDateFormat : this.#mediumDateFormat;
		}

		return withFullMonth ? this.#dateFormat : this.#shortDateFormat;
	}

	toTimeString(now: Date, utc: boolean): string
	{
		return DateTimeFormat.format(this.#timeFormat, this.#datetime, now, utc).replaceAll('\\', '');
	}

	toDateString(options: DateFormatOptions = {}): string
	{
		const withFullMonth = Boolean(options.withFullMonth ?? true);
		const showYear = this.#isShowYear();

		return (
			DateTimeFormat.format(
				[
					['today', 'today'],
					['tommorow', 'tommorow'],
					['yesterday', 'yesterday'],
					['', this.#getDateFormat(withFullMonth, showYear)],
				],
				this.#datetime,
			).replaceAll('\\', '')
		);
	}

	#isShowYear(): boolean
	{
		return this.#datetime.getFullYear() !== (Timezone.UserTime.getDate()).getFullYear();
	}

	toFormatString(format: string, now: Date, utc: boolean): string
	{
		return DateTimeFormat.format(format, this.#datetime, now, utc).replaceAll('\\', '');
	}

	static getSiteDateFormat(): string
	{
		return DateTimeFormat.getFormat('FORMAT_DATE');
	}

	static getSiteDateTimeFormat(): string
	{
		return DateTimeFormat.getFormat('FORMAT_DATETIME');
	}
}
