import { DateTimeFormat } from 'main.date';

export class Duration
{
	constructor(milliseconds: number)
	{
		this.milliseconds = Math.abs(milliseconds);
	}

	static createFromSeconds(seconds: number): Duration
	{
		return new Duration(seconds * Duration.getUnitDurations().s);
	}

	static createFromMinutes(minutes: number): Duration
	{
		return new Duration(minutes * Duration.getUnitDurations().i);
	}

	get seconds(): number
	{
		return Math.floor(this.milliseconds / Duration.getUnitDurations().s);
	}

	get minutes(): number
	{
		return Math.floor(this.milliseconds / Duration.getUnitDurations().i);
	}

	get hours(): number
	{
		return Math.floor(this.milliseconds / Duration.getUnitDurations().H);
	}

	get days(): number
	{
		return Math.floor(this.milliseconds / Duration.getUnitDurations().d);
	}

	/**
	 * Duration in months (considering that a month is 31 days)
	 */
	get months(): number
	{
		return Math.floor(this.milliseconds / Duration.getUnitDurations().m);
	}

	/**
	 * Duration in years (considering that a year is 365 days)
	 */
	get years(): number
	{
		return Math.floor(this.milliseconds / Duration.getUnitDurations().Y);
	}

	/**
	 * Available units: `s` - seconds, `i` - minutes, `H` - hours, `d` - days, `m` - months, `Y` - years.
	 *
	 * If not pass format string then:
	 * - Duration will be formatted automatically with 'Y m d H i s'
	 * @example '1 day 2 hours 20 minutes'
	 * - Units will be taken with mod:
	 * @example result will be '1 hour 30 minutes' instead of '1 hour 90 minutes 3600 seconds'
	 * - Zero units will not be shown
	 * @example result will be '1 hour' instead of '1 hour 0 minutes 0 seconds'
	 */
	format(formatStr: string = ''): string
	{
		if (formatStr === '')
		{
			return this.formatAllUnits('Y m d H i s', true).replaceAll(/\s+/g, ' ').trim();
		}

		return this.formatAllUnits(formatStr, false);
	}

	formatAllUnits(formatStr: string, mod: boolean): string
	{
		// eslint-disable-next-line unicorn/better-regex
		return formatStr.replaceAll(/([YmdHis])/g, (unitStr) => this.formatUnit(unitStr, mod));
	}

	formatUnit(unitStr: string, mod: boolean): string
	{
		const value = mod ? this.getUnitPropertyModByFormat(unitStr) : this.getUnitPropertyByFormat(unitStr);
		if (mod && value === 0)
		{
			return '';
		}

		const now = Date.now() / 1000;
		const unitDuration = value * this.getUnitDuration(unitStr) / 1000;

		return DateTimeFormat.format(`${unitStr}diff`, now - unitDuration, now);
	}

	getUnitPropertyByFormat(unitStr: string): number
	{
		const props = { s: this.seconds, i: this.minutes, H: this.hours, d: this.days, m: this.months, Y: this.years };

		return props[unitStr];
	}

	getUnitPropertyModByFormat(unitStr: string): number
	{
		const propsMod = {
			s: this.seconds % 60,
			i: this.minutes % 60,
			H: this.hours % 24,
			d: this.days % 31,
			m: this.months % 12,
			Y: this.years,
		};

		return propsMod[unitStr];
	}

	getUnitDuration(unitStr: string): number
	{
		return Duration.getUnitDurations()[unitStr];
	}

	static getUnitDurations(): { s: number, i: number, H: number, d: number, m: number, Y: number }
	{
		return {
			s: 1000,
			i: 60000,
			H: 3_600_000,
			d: 86_400_000,
			m: 2_678_400_000,
			Y: 31_536_000_000,
		};
	}
}
