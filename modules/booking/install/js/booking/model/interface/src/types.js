import { DraggedElementKind } from 'booking.const';

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
	waitListExpanded: boolean,
	calendarExpanded: boolean,
	fromHour: number,
	toHour: number,
	selectedDateTs: number,
	viewDateTs: number,
	deletingBookings: { [key: number ]: number },
	deletingResources: { [key: number ]: number },
	deletingWaitListItemIds: { [key: number ]: number },
	selectedCells: { [key: string ]: Object },
	hoveredCell: CellDto,
	busySlots: { [key: string ]: Object },
	disabledBusySlots: { [key: string ]: Object },
	resourcesIds: number[],
	pinnedResourceIds: number[],
	isIntersectionForAll: boolean,
	counterMarks: string[],
	freeMarks: string[],
	totalClients: number,
	totalNewClientsToday: number,
	moneyStatistics: MoneyStatistics | null,
	intersections: Intersections,
	timezone: string,
	editingBookingId: number,
	editingWaitListItemId: number,
	draggedBookingId: number,
	draggedBookingResourceId: number,
	draggedDataTransfer: DraggedDataTransfer,
	resizedBookingId: number,
	mousePosition: MousePosition,
	isCurrentSenderAvailable: boolean,
	isShownTrialPopup: boolean,
	animationPause: boolean,
	createdFromEmbedBookings: { [key: number | string ]: number | string },
	createdFromEmbedWaitListItems: { [key: number | string ]: number | string },
}

export type Intersections = {
	[resourceId: number | 0]: number[],
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

export type DraggedDataTransfer = {
	id: number,
	resourceId: number,
	kind: $Values<typeof DraggedElementKind> | null,
}
