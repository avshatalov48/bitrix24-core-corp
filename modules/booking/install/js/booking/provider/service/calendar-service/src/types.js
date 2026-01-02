export type CalendarGetBookingsDatesResponse = {
	foundDates: string[],
	foundDatesWithCounters: string[],
};

export type CalendarGetBookingsDatesCountResponse = {
	count: number,
	minDate: string,
	maxDate: string,
}
