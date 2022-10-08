import {Loc} from "main.core";
import {DateTimeFormat} from "main.date";

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
		let serverTimezoneOffset = parseInt(Loc.getMessage('CRM_TIMELINE_SERVER_TZ_OFFSET'));
		if(isNaN(serverTimezoneOffset))
		{
			serverTimezoneOffset = 0;
		}
		const clientTimezoneOffset = - (new Date()).getTimezoneOffset() * 60;

		const timestampInClientTz = timestamp + serverTimezoneOffset - clientTimezoneOffset;

		const date = new Date(timestampInClientTz * 1000);

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
		this.#datetime = new Date(this.#datetime.getTime() + 1000 * DatetimeConverter.getUserTimezoneOffset());

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
		);
	}

	toTimeString(now: Date, utc: boolean): string
	{
		return DateTimeFormat.format(this.#timeFormat, this.#datetime, now, utc);
	}

	toDateString(): string
	{
		return (
			DateTimeFormat.format(
				[
					['today', 'today'],
					['tommorow', 'tommorow'],
					['yesterday', 'yesterday'],
					['', (this.#datetime.getFullYear() === (new Date()).getFullYear() ?  this.#shortDateFormat :  this.#fullDateFormat)]
				],
				this.#datetime
			)
		);
	}

	static getUserTimezoneOffset(): Number
	{
		if(!this.userTimezoneOffset)
		{
			this.userTimezoneOffset = parseInt(Loc.getMessage('USER_TZ_OFFSET'));
			if(isNaN(this.userTimezoneOffset))
			{
				this.userTimezoneOffset = 0;
			}
		}
		return this.userTimezoneOffset;
	}

	static getSiteDateFormat(): string
	{
		return DateTimeFormat.convertBitrixFormat(Loc.getMessage('FORMAT_DATE'));
	}

	static getSiteDateTimeFormat(): string
	{
		return DateTimeFormat.convertBitrixFormat(Loc.getMessage('FORMAT_DATETIME'));
	}

	static userTimezoneOffset;
}
