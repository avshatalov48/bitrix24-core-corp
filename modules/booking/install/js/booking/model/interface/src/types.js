export type InterfaceModelState = {
	isFeatureEnabled: boolean,
	canTurnOnTrial: boolean,
	canTurnOnDemo: boolean,
	isLoaded: boolean,
	zoom: number,
	expanded: boolean,
	scroll: number,
	offHoursHover: boolean,
	offHoursExpanded: boolean,
	fromHour: number,
	toHour: number,
	selectedDateTs: number,
	viewDateTs: number,
	deletingBookings: { [key: number ]: number },
	selectedCells: { [key: string ]: Object },
	hoveredCell: CellDto,
	busySlots: { [key: string ]: Object },
	disabledBusySlots: { [key: string ]: Object },
	resourcesIds: number[],
	isFilterMode: boolean,
	isIntersectionForAll: boolean,
	filteredBookingsIds: number[],
	filteredMarks: string[],
	counterMarks: string[],
	freeMarks: string[],
	totalClients: number,
	totalNewClientsToday: number,
	moneyStatistics: MoneyStatistics | null,
	intersections: Intersections,
	quickFilter: QuickFilter,
	timezone: string,
	editingBookingId: number,
	draggedBookingId: number,
	draggedBookingResourceId: number,
	resizedBookingId: number,
	mousePosition: MousePosition,
	isCurrentSenderAvailable: boolean,
	isShownTrialPopup: boolean,
}

export type Intersections = {
	[resourceId: number | 0]: number[],
};

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

export type MousePosition = {
	top: number,
	left: number,
};

export type MoneyStatistics = {
	today: {
		currencyId: string,
		opportunity: number,
	}[],
	month: {
		currencyId: string,
		opportunity: number,
	}[],
};

export type Occupancy = {
	fromTs: number,
	toTs: number,
	resourcesIds: number[],
};
