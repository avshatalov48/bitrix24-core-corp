import { DateTimeFormat } from 'main.date';

class TimeFormatter
{
	parseTime(value: string, previousTimestamp: number): number
	{
		if (this.#isIncorrectTimeValue(value))
		{
			return previousTimestamp;
		}

		let time = this.getMaskedTime(value);
		time = this.#beautifyTime(time);
		if (DateTimeFormat.isAmPmMode())
		{
			let amPmSymbol = (value.toLowerCase().match(/[ap]/g) ?? []).pop();
			if (!amPmSymbol)
			{
				const hour = Number(this.#getMinutesAndHours(time).hours);
				if (hour >= 8 && hour <= 11)
				{
					amPmSymbol = 'a';
				}
				else
				{
					amPmSymbol = 'p';
				}
			}

			if (amPmSymbol === 'a')
			{
				time += ' am';
			}

			if (amPmSymbol === 'p')
			{
				time += ' pm';
			}
		}

		return new Date(`${DateTimeFormat.format('Y-m-d', previousTimestamp / 1000)} ${time}`).getTime();
	}

	getMaskedTime(value: string, key: string): string
	{
		let time = '';
		const { hours, minutes } = this.#getMinutesAndHours(value, key);
		if (hours && !minutes)
		{
			time = String(hours);
			if (value.length - time.length === 1 || value.includes(':'))
			{
				time += ':';
			}
		}

		if (hours && minutes)
		{
			time = `${hours}:${minutes}`;
		}

		if (DateTimeFormat.isAmPmMode() && this.#clearTimeString(time) !== '')
		{
			const amPmSymbol = (value.toLowerCase().match(/[ap]/g) ?? []).pop();
			if (amPmSymbol === 'a')
			{
				time = `${this.#beautifyTime(time)} am`;
			}

			if (amPmSymbol === 'p')
			{
				time = `${this.#beautifyTime(time)} pm`;
			}
		}

		return time;
	}

	formatTime(timestamp: number): string
	{
		const format = DateTimeFormat.getFormat('SHORT_TIME_FORMAT');

		return DateTimeFormat.format(format, timestamp / 1000);
	}

	#isIncorrectTimeValue(timeValue: string): boolean
	{
		if (DateTimeFormat.isAmPmMode())
		{
			return timeValue === '';
		}

		const date = new Date(`${DateTimeFormat.format('Y-m-d')} ${timeValue}`);

		return timeValue === '' || (timeValue[0] !== '0' && date.getHours() === 0);
	}

	#beautifyTime(time: string): string
	{
		if (this.#clearTimeString(time) === '')
		{
			return '';
		}

		if (!time.includes(':'))
		{
			time += ':00';
		}

		if (time.indexOf(':') === time.length - 1)
		{
			time += '00';
		}

		let { hours, minutes } = this.#getMinutesAndHours(time);
		hours = `0${hours}`.slice(-2);
		minutes = `0${minutes}`.slice(-2);

		return `${hours}:${minutes}`;
	}

	#getMinutesAndHours(value: string, key: string): { hours: string, minutes: string }
	{
		const time = this.#clearTimeString(value, key);
		let hours = 0;
		let minutes = 0;

		if (time.includes(':'))
		{
			hours = time.match(/\d*:/g)[0].slice(0, -1);
			minutes = time.match(/:\d*/g)[0].slice(1);
		}
		else
		{
			const digits = (time.match(/\d/g) ?? []).splice(0, 4).map((d) => Number(d));
			if (digits.length === 4 && digits[0] > this.#getMaxHours() / 10)
			{
				digits.pop();
			}

			if (digits.length === 1)
			{
				hours = String(digits[0]);
			}

			if (digits.length === 2)
			{
				hours = `${digits[0]}${digits[1]}`;
				if (Number(hours) > this.#getMaxHours())
				{
					hours = String(digits[0]);
					minutes = String(digits[1]);
				}
			}

			if (digits.length === 3)
			{
				if (DateTimeFormat.isAmPmMode())
				{
					if (digits[0] >= 1)
					{
						hours = String(digits[0]);
						minutes = `${digits[1]}${digits[2]}`;
					}
					else
					{
						hours = `${digits[0]}${digits[1]}`;
						minutes = String(digits[2]);
					}
				}
				else if (Number(`${digits[0]}${digits[1]}`) < 24)
				{
					hours = `${digits[0]}${digits[1]}`;
					minutes = String(digits[2]);
				}
				else
				{
					hours = String(digits[0]);
					minutes = `${digits[1]}${digits[2]}`;
				}
			}

			if (digits.length === 4)
			{
				hours = `${digits[0]}${digits[1]}`;
				minutes = `${digits[2]}${digits[3]}`;
			}
		}

		if (hours)
		{
			hours = this.#formatHours(hours);
		}

		if (minutes)
		{
			minutes = this.#formatMinutes(minutes);
		}

		return { hours, minutes };
	}

	#clearTimeString(str: string, key: string): string
	{
		let validatedTime = str.replaceAll(/[ap]/g, '').replaceAll(/\D/g, ':'); // remove a and p and replace not digits to :
		validatedTime = validatedTime.replace(/:*/, ''); // remove everything before first digit

		// leave only first :
		const firstColonIndex = validatedTime.indexOf(':');
		validatedTime = validatedTime.slice(0, firstColonIndex + 1) + validatedTime.slice(firstColonIndex + 1).replaceAll(':', '');

		// leave not more than 2 hour digits and 2 minute digits
		if (firstColonIndex !== -1)
		{
			const hours = this.#formatHours(validatedTime.match(/\d*:/g)[0].slice(0, -1));
			const minutes = validatedTime.match(/:\d*/g)[0].slice(1).slice(0, 3);
			if (hours.length === 1 && minutes.length === 3 && !Number.isNaN(Number(key)) && this.#areTimeDigitsCorrect(`${hours}${minutes}`))
			{
				return `${hours}${minutes}`;
			}

			return `${hours}:${minutes}`;
		}

		return validatedTime.slice(0, 4);
	}

	#areTimeDigitsCorrect(time): boolean
	{
		const hh = time.slice(0, 2);
		const mm = time.slice(2);

		return this.#formatHours(hh) === hh && this.#formatMinutes(mm) === mm;
	}

	#formatHours(hours: string): string
	{
		if (DateTimeFormat.isAmPmMode())
		{
			return hours;
		}

		const firstDigit = hours[0];
		if (Number(firstDigit) > this.#getMaxHours() / 10)
		{
			return `0${firstDigit}`;
		}

		if (Number(hours) <= this.#getMaxHours())
		{
			return `${firstDigit}${hours[1] ?? ''}`;
		}

		return String(firstDigit);
	}

	#formatMinutes(minutes: string): string
	{
		const firstDigit = minutes[0];

		return firstDigit >= 6 ? `0${firstDigit}` : `${firstDigit}${minutes[1] ?? ''}`;
	}

	#getMaxHours(): number
	{
		return DateTimeFormat.isAmPmMode() ? 12 : 24;
	}
}

export const timeFormatter = new TimeFormatter();
