import { DateTimeFormat, Timezone } from 'main.date';
import { Text } from 'main.core';

declare type DateTimeFormatOptions = {
	withDayOfWeek: Boolean,
	delimiter: string
};

export default class DatetimeConverter
{
	#timeFormat: string;
	#shortDateFormat: string;
	#fullDateFormat: string;
	#datetime: ?Date = null; // date object which absolute time will be the same as if it was in server timezone

	/**
	 * @param timestamp Normal UTC timestamp, as it should be
	 */
	static createFromServerTimestamp(timestamp: Number): DatetimeConverter
	{
		const offset = BX.Main.Timezone.Offset.SERVER_TO_UTC + Text.toInteger((new Date()).getTimezoneOffset() * 60);

		// make a date object which absolute time will match time of server (even though it has different timezone)
		const date = new Date((timestamp + offset) * 1000);

		return new DatetimeConverter(date);
	}

	constructor(datetime: Date)
	{
		this.#timeFormat = DateTimeFormat.getFormat('SHORT_TIME_FORMAT');
		this.#shortDateFormat = DateTimeFormat.getFormat('DAY_SHORT_MONTH_FORMAT');
		this.#fullDateFormat = DateTimeFormat.getFormat('MEDIUM_DATE_FORMAT');

		this.#datetime = datetime;
	}
	getValue(): Date
	{
		return this.#datetime;
	}

	toUserTime(): DatetimeConverter
	{
		const timestampServer = Math.floor(this.#datetime.getTime() / 1000);

		// make a date object which absolute time will match time of user (even though it has different timezone)
		this.#datetime = new Date((timestampServer + Timezone.Offset.USER_TO_SERVER) * 1000);

		return this;
	}

	toDatetimeString(options: DateTimeFormatOptions): string
	{
		options = options || {};

		const now = new Date();
		const withDayOfWeek = !!options.withDayOfWeek;
		const delimiter = options.delimiter || ' ';

		return DateTimeFormat.format(
			[
				[ 'today', 'today' + delimiter + this.#timeFormat ],
				[ 'tommorow', 'tommorow' + delimiter + this.#timeFormat ],
				[ 'yesterday', 'yesterday' + delimiter + this.#timeFormat ],
				[
					'',
					(withDayOfWeek ? 'D'+ delimiter : '')
					+ (this.#datetime.getFullYear() === now.getFullYear() ? this.#shortDateFormat :  this.#fullDateFormat)
					+ delimiter
					+ this.#timeFormat
				]
			],
			this.#datetime,
			now
		).replaceAll('\\', '');
	}

	toTimeString(now: Date, utc: boolean): string
	{
		return DateTimeFormat.format(this.#timeFormat, this.#datetime, now, utc).replaceAll('\\', '');
	}

	toDateString(): string
	{
		return (
			DateTimeFormat.format(
				[
					['today', 'today'],
					['tommorow', 'tommorow'],
					['yesterday', 'yesterday'],
					['', (this.#datetime.getFullYear() === (Timezone.UserTime.getDate()).getFullYear() ?  this.#shortDateFormat :  this.#fullDateFormat)]
				],
				this.#datetime
			).replaceAll('\\', '')
		);
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
