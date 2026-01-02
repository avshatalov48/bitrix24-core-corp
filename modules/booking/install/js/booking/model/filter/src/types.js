export type FilterModelState = {
	datesCount: DatesCount,
	fields: FilterFields,
	filterDates: number[],
	fetchingNextDate: boolean,
	filteredBookingsIds: number[],
	filteredMarks: string[],
	isFilterMode: boolean,
	quickFilter: QuickFilter,
	deletingResourceFilter: DeletingResourceFilter | null;
}

export type QuickFilter = {
	hovered: {
		[hour: number]: number,
	},
	active: {
		[hour: number]: number,
	},
	ignoredBookingIds: {
		[bookingId: number]: number,
	},
};

export type DatesCount = {
	count: number,
	minDate: string,
	maxDate: string,
};

export type FilterFields = { [key: string]: string | string[] }

export type DeletingResourceFilter = {
	resourceId: number;
	requestFields: ?FilterFields;
};
