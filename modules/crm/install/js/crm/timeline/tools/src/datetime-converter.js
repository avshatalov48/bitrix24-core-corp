import { Loc } from "main.core";
import { DateTimeFormat } from "main.date";
import { TimestampConverter, Factory } from "crm.datetime";

declare type DateTimeFormatOptions = {
	withDayOfWeek: Boolean,
	delimiter: string
};

export default class DatetimeConverter
{
	#timeFormat: string;
	#shortDateFormat: string;
	#fullDateFormat: string;
	#datetime: ?Date = null;

	/**
	 * @param timestamp Timestamp in server timezone
	 */
	static createFromServerTimestamp(timestamp: Number): DatetimeConverter
	{
		const date = Factory.createFromTimestampInServerTimezone(timestamp);

		return new DatetimeConverter(date);
	}

	constructor(datetime: Date)
	{
		this.#timeFormat = Loc.getMessage('CRM_TIMELINE_TIME_FORMAT');
		this.#shortDateFormat = Loc.getMessage('CRM_TIMELINE_SHORT_DATE_FORMAT');
		this.#fullDateFormat = Loc.getMessage('CRM_TIMELINE_FULL_DATE_FORMAT');

		this.#datetime = datetime;
	}
	getValue(): Date
	{
		return this.#datetime;
	}

	toUserTime(): DatetimeConverter
	{
		const serverTimestamp = Math.floor(this.#datetime.getTime() / 1000);
		this.#datetime = new Date(TimestampConverter.serverToUser(serverTimestamp) * 1000);

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
					['', (this.#datetime.getFullYear() === (Factory.getUserNow()).getFullYear() ?  this.#shortDateFormat :  this.#fullDateFormat)]
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
		return DateTimeFormat.convertBitrixFormat(Loc.getMessage('FORMAT_DATE'));
	}

	static getSiteDateTimeFormat(): string
	{
		return DateTimeFormat.convertBitrixFormat(Loc.getMessage('FORMAT_DATETIME'));
	}
}
