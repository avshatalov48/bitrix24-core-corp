import { DateFormat } from 'booking.const';
import { Duration } from 'booking.lib.duration';
import { Timezone } from 'booking.lib.timezone';
import type { SlotRange } from 'booking.model.resources';

export function applyTimezone(slotRanges: SlotRange[], dateTs: number, timezone: string): SlotRange[]
{
	const minutesInDay = Duration.getUnitDurations().d / Duration.getUnitDurations().i;
	const timezoneOffset = Timezone.getOffset(dateTs, timezone);

	return slotRanges.map((slotRange: SlotRange): SlotRange => {
		const slotTimezoneOffset = Timezone.getOffset(dateTs, slotRange.timezone);
		const minutesOffset = (timezoneOffset - slotTimezoneOffset) / 60;

		return {
			...slotRange,
			from: slotRange.from + minutesOffset,
			to: slotRange.to + minutesOffset,
		};
	}).map((slotRange: SlotRange) => {
		if (slotRange.from > minutesInDay)
		{
			return {
				...slotRange,
				from: slotRange.from - minutesInDay,
				to: slotRange.to - minutesInDay,
				weekDays: slotRange.weekDays.map((weekDay) => getNextDay(weekDay)),
			};
		}

		if (slotRange.to < 0)
		{
			return {
				...slotRange,
				from: slotRange.from + minutesInDay,
				to: slotRange.to + minutesInDay,
				weekDays: slotRange.weekDays.map((weekDay) => getPreviousDay(weekDay)),
			};
		}

		return slotRange;
	}).flatMap((slotRange: SlotRange): SlotRange[] => {
		if (slotRange.from < 0)
		{
			return [
				{
					...slotRange,
					from: 0,
				},
				...slotRange.weekDays.map((weekDay) => ({
					...slotRange,
					from: minutesInDay + slotRange.from,
					to: minutesInDay,
					weekDays: [getPreviousDay(weekDay)],
				})),
			];
		}

		if (slotRange.to > minutesInDay)
		{
			return [
				{
					...slotRange,
					to: minutesInDay,
				},
				...slotRange.weekDays.map((weekDay) => ({
					...slotRange,
					from: 0,
					to: slotRange.to - minutesInDay,
					weekDays: [getNextDay(weekDay)],
				})),
			];
		}

		return slotRange;
	});
}

function getNextDay(weekDay: string): string
{
	return DateFormat.WeekDays[(DateFormat.WeekDays.indexOf(weekDay) + 1) % 7];
}

function getPreviousDay(weekDay: string): string
{
	return DateFormat.WeekDays[(DateFormat.WeekDays.indexOf(weekDay) + 7 - 1) % 7];
}
