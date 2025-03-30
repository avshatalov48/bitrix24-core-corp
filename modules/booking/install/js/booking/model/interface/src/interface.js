import { Type } from 'main.core';
import { BuilderModel } from 'ui.vue3.vuex';
import type { GetterTree, ActionTree, MutationTree } from 'ui.vue3.vuex';

import { Model, BusySlot } from 'booking.const';
import type { BusySlotDto } from 'booking.lib.busy-slots';
import type { BookingModel } from 'booking.model.bookings';
import type {
	InterfaceModelState,
	Intersections,
	MousePosition,
	MoneyStatistics,
	QuickFilter,
	Occupancy,
} from './types';

export class Interface extends BuilderModel
{
	getName(): string
	{
		return Model.Interface;
	}

	getState(): InterfaceModelState
	{
		const today = new Date();
		const schedule = this.getVariable('schedule', {});

		return {
			isFeatureEnabled: this.getVariable('isFeatureEnabled', false),
			canTurnOnTrial: this.getVariable('canTurnOnTrial', false),
			canTurnOnDemo: this.getVariable('canTurnOnDemo', false),
			editingBookingId: this.getVariable('editingBookingId', 0),
			draggedBookingId: 0,
			draggedBookingResourceId: 0,
			resizedBookingId: 0,
			isLoaded: false,
			zoom: 1,
			expanded: false,
			scroll: 0,
			offHoursHover: false,
			offHoursExpanded: false,
			fromHour: schedule.fromHour ?? 9,
			toHour: schedule.toHour ?? 19,
			selectedDateTs: new Date(today.getFullYear(), today.getMonth(), today.getDate()).getTime(),
			viewDateTs: new Date(today.getFullYear(), today.getMonth()).getTime(),
			deletingBookings: {},
			selectedCells: {},
			hoveredCell: null,
			busySlots: {},
			disabledBusySlots: {},
			resourcesIds: [],
			isFilterMode: false,
			isIntersectionForAll: true,
			filteredBookingsIds: [],
			filteredMarks: [],
			counterMarks: [],
			freeMarks: [],
			totalClients: this.getVariable('totalClients', 0),
			totalNewClientsToday: this.getVariable('totalNewClientsToday', 0),
			moneyStatistics: this.getVariable('moneyStatistics', null),
			intersections: {},
			quickFilter: {
				hovered: {},
				active: {},
				ignoredBookingIds: {},
			},
			timezone: this.getVariable('timezone', Intl.DateTimeFormat().resolvedOptions().timeZone),
			mousePosition: {
				top: 0,
				left: 0,
			},
			isCurrentSenderAvailable: false,
			isShownTrialPopup: false,
		};
	}

	// eslint-disable-next-line max-lines-per-function
	getGetters(): GetterTree<InterfaceModelState>
	{
		return {
			/** @function interface/isFeatureEnabled */
			isFeatureEnabled: (state): boolean => {
				return state.isFeatureEnabled || state.canTurnOnTrial;
			},
			/** @function interface/canTurnOnTrial */
			canTurnOnTrial: (state): boolean => state.canTurnOnTrial,
			/** @function interface/canTurnOnDemo */
			canTurnOnDemo: (state): boolean => state.canTurnOnDemo,
			/** @function interface/isShownTrialPopup */
			isShownTrialPopup: (state): boolean => state.isShownTrialPopup,
			/** @function interface/editingBookingId */
			editingBookingId: (state): number => state.editingBookingId,
			/** @function interface/isEditingBookingMode */
			isEditingBookingMode: (state): boolean => state.editingBookingId > 0,
			/** @function interface/draggedBookingId */
			draggedBookingId: (state): number => state.draggedBookingId,
			/** @function interface/draggedBookingResourceId */
			draggedBookingResourceId: (state): number => state.draggedBookingResourceId,
			/** @function interface/resizedBookingId */
			resizedBookingId: (state): number => state.resizedBookingId,
			/** @function interface/isDragMode */
			isDragMode: (state): boolean => state.draggedBookingId || state.resizedBookingId,
			/** @function interface/isLoaded */
			isLoaded: (state): boolean => state.isLoaded,
			/** @function interface/zoom */
			zoom: (state): number => state.zoom,
			/** @function interface/expanded */
			expanded: (state): boolean => state.expanded,
			/** @function interface/scroll */
			scroll: (state): number => state.scroll,
			/** @function interface/offHoursHover */
			offHoursHover: (state): number => state.offHoursHover,
			/** @function interface/offHoursExpanded */
			offHoursExpanded: (state): number => state.offHoursExpanded,
			/** @function interface/fromHour */
			fromHour: (state): number => state.fromHour,
			/** @function interface/toHour */
			toHour: (state): number => state.toHour,
			/** @function interface/selectedDateTs */
			selectedDateTs: (state, getters): number => state.selectedDateTs - getters.offset,
			/** @function interface/viewDateTs */
			viewDateTs: (state, getters): number => state.viewDateTs - getters.offset,
			/** @function interface/deletingBookings */
			deletingBookings: (state): { [id: number]: number } => state.deletingBookings,
			/** @function interface/selectedCells */
			selectedCells: (state): { [id: string]: CellDto } => state.selectedCells,
			/** @function interface/hoveredCell */
			hoveredCell: (state): CellDto | null => state.hoveredCell,
			/** @function interface/busySlots */
			busySlots: (state): BusySlotDto[] => Object.values(state.busySlots),
			/** @function interface/disabledBusySlots */
			disabledBusySlots: (state): { [id: string]: BusySlotDto } => state.disabledBusySlots,
			/** @function interface/resourcesIds */
			resourcesIds: (state, getters, rootState, rootGetters): number[] => {
				const extraResourcesIds = rootGetters[`${Model.Bookings}/getByDate`](state.selectedDateTs)
					.filter((booking) => booking.counter > 0)
					.map((booking) => booking.resourcesIds[0])
				;

				const resourcesIds = [...new Set([...state.resourcesIds, ...extraResourcesIds])];

				const excludeResources = new Set(
					getters.getOccupancy(resourcesIds, Object.values(state.quickFilter.ignoredBookingIds))
						.filter((occupancy: Occupancy) => Object.values(state.quickFilter.active).some((hour) => {
							const fromTs = new Date(state.selectedDateTs).setHours(hour) - getters.offset;
							const toTs = new Date(state.selectedDateTs).setHours(hour + 1) - getters.offset;

							return toTs > occupancy.fromTs && fromTs < occupancy.toTs;
						}))
						.flatMap((occupancy: Occupancy) => occupancy.resourcesIds),
				);

				return resourcesIds.filter((id) => !excludeResources.has(id));
			},
			extraResourcesIds: (state, getters) => {
				const resourcesIds = state.resourcesIds;

				return getters.resourcesIds.filter((id) => !resourcesIds.includes(id));
			},
			/** @function interface/isFilterMode */
			isFilterMode: (state): boolean => state.isFilterMode,
			/** @function interface/isIntersectionForAll */
			isIntersectionForAll: (state): boolean => state.isIntersectionForAll,
			/** @function interface/filteredBookingsIds */
			filteredBookingsIds: (state): number[] => state.filteredBookingsIds,
			/** @function interface/filteredMarks */
			filteredMarks: (state): string[] => state.filteredMarks,
			/** @function interface/freeMarks */
			freeMarks: (state): string[] => state.freeMarks,
			/** @function interface/totalClients */
			totalClients: (state): number => state.totalClients,
			/** @function interface/totalNewClientsToday */
			totalNewClientsToday: (state): number => state.totalNewClientsToday,
			/** @function interface/moneyStatistics */
			moneyStatistics: (state): MoneyStatistics | null => state.moneyStatistics,
			getCounterMarks: (state) => (filterDates: string[] | null = null) => {
				if (Type.isNull(filterDates))
				{
					return state.counterMarks;
				}

				return state.counterMarks.filter((date) => filterDates.includes(date));
			},
			/** @function interface/intersections */
			intersections: (state): Intersections => state.intersections,
			/** @function interface/quickFilter */
			quickFilter: (state): QuickFilter => state.quickFilter,
			/** @function interface/timezone */
			timezone: (state): string => state.timezone,
			/** @function interface/timezoneOffset */
			timezoneOffset: (state): number => {
				const timeZone = state.timezone;
				const date = new Date(state.selectedDateTs);
				const dateInTimezone = new Date(date.toLocaleString('en-US', { timeZone }));
				const dateInUTC = new Date(date.toLocaleString('en-US', { timeZone: 'UTC' }));

				return (dateInTimezone.getTime() - dateInUTC.getTime()) / 1000;
			},
			/** @function interface/offset */
			offset: (state, getters): number => {
				return (getters.timezoneOffset + new Date(state.selectedDateTs).getTimezoneOffset() * 60) * 1000;
			},
			/** @function interface/mousePosition */
			mousePosition: (state): MousePosition => state.mousePosition,
			/** @function interface/isCurrentSenderAvailable */
			isCurrentSenderAvailable: (state): boolean => state.isCurrentSenderAvailable,
			/** @function interface/getColliding */
			getColliding: (state, getters) => {
				return (resourceId: number, excludedBookingIds: number[]): Occupancy[] => [
					...getters.getOccupancy([resourceId], excludedBookingIds),
					...Object.values(state.selectedCells)
						.filter((cell: CellDto) => cell.resourceId === resourceId)
						.map((cell: CellDto) => ({
							fromTs: cell.fromTs,
							toTs: cell.toTs,
							resourcesIds: [cell.resourceId],
						})),
				];
			},
			/** @function interface/getOccupancy */
			getOccupancy: (state, getters, rootState, rootGetters) => {
				return (resourcesIds: number[], excludedBookingIds: number[] = []): Occupancy[] => {
					const resources = new Set(resourcesIds);

					const bookings = rootGetters[`${Model.Bookings}/get`]
						.filter((booking: BookingModel) => {
							const belongsToResources = booking.resourcesIds.some((id) => resources.has(id));
							const isNotExcluded = !excludedBookingIds.includes(booking.id);

							return belongsToResources && isNotExcluded;
						})
						.map((booking: BookingModel) => ({
							fromTs: booking.dateFromTs,
							toTs: booking.dateToTs,
							resourcesIds: booking.resourcesIds,
						}))
					;

					const busySlots = Object.values(state.busySlots)
						.filter((busySlot: BusySlotDto) => {
							const isDragOffHours = getters.isDragMode && busySlot.type === BusySlot.OffHours;
							const isActive = !(busySlot.id in state.disabledBusySlots) && !isDragOffHours;
							const belongsToResources = resources.has(busySlot.resourceId);

							return isActive && belongsToResources;
						})
						.map(({ fromTs, toTs, resourceId }) => ({ fromTs, toTs, resourcesIds: [resourceId] }))
					;

					return [...bookings, ...busySlots];
				};
			},
		};
	}

	// eslint-disable-next-line max-lines-per-function
	getActions(): ActionTree<InterfaceModelState>
	{
		return {
			/** @function interface/setEditingBookingId */
			setEditingBookingId: (store, editingBookingId: number) => {
				store.commit('setEditingBookingId', editingBookingId);
			},
			/** @function interface/setDraggedBookingId */
			setDraggedBookingId: (store, draggedBookingId: number) => {
				store.commit('setDraggedBookingId', draggedBookingId);
			},
			/** @function interface/setDraggedBookingResourceId */
			setDraggedBookingResourceId: (store, draggedBookingResourceId: number) => {
				store.commit('setDraggedBookingResourceId', draggedBookingResourceId);
			},
			/** @function interface/setResizedBookingId */
			setResizedBookingId: (store, resizedBookingId: number) => {
				store.commit('setResizedBookingId', resizedBookingId);
			},
			/** @function interface/setIsLoaded */
			setIsLoaded: (store, isLoaded: boolean) => {
				store.commit('setIsLoaded', isLoaded);
			},
			/** @function interface/setZoom */
			setZoom: (store, zoom: number) => {
				store.commit('setZoom', zoom);
			},
			/** @function interface/setExpanded */
			setExpanded: (store, expanded: boolean) => {
				store.commit('setExpanded', expanded);
			},
			/** @function interface/setScroll */
			setScroll: (store, scroll: number) => {
				store.commit('setScroll', scroll);
			},
			/** @function interface/setOffHoursHover */
			setOffHoursHover: (store, offHoursHover: boolean) => {
				store.commit('setOffHoursHover', offHoursHover);
			},
			/** @function interface/setOffHoursExpanded */
			setOffHoursExpanded: (store, offHoursExpanded: boolean) => {
				store.commit('setOffHoursExpanded', offHoursExpanded);
			},
			/** @function interface/setSelectedDateTs */
			setSelectedDateTs: (store, selectedDateTs: number) => {
				store.commit('setSelectedDateTs', selectedDateTs);
			},
			/** @function interface/setViewDateTs */
			setViewDateTs: (store, viewDateTs: number) => {
				store.commit('setViewDateTs', viewDateTs);
			},
			/** @function interface/addDeletingBooking */
			addDeletingBooking: (store, bookingId: number) => {
				store.commit('addDeletingBooking', bookingId);
			},
			/** @function interface/removeDeletingBooking */
			removeDeletingBooking: (store, bookingId: number) => {
				store.commit('removeDeletingBooking', bookingId);
			},
			/** @function interface/addSelectedCell */
			addSelectedCell: (store, cell: CellDto) => {
				store.commit('addSelectedCell', cell);
			},
			/** @function interface/removeSelectedCell */
			removeSelectedCell: (store, cell: CellDto) => {
				store.commit('removeSelectedCell', cell);
			},
			/** @function interface/clearSelectedCells */
			clearSelectedCells: (store) => {
				store.commit('clearSelectedCells');
			},
			/** @function interface/setHoveredCell */
			setHoveredCell: (store, cell: CellDto | null) => {
				store.commit('setHoveredCell', cell);
			},
			/** @function interface/upsertBusySlotMany */
			upsertBusySlotMany: (store: Store, busySlots: BusySlotDto[]): void => {
				busySlots.forEach((busySlot) => store.commit('upsertBusySlot', busySlot));
			},
			/** @function interface/clearBusySlots */
			clearBusySlots: (store) => {
				store.commit('clearBusySlots');
			},
			/** @function interface/addDisabledBusySlot */
			addDisabledBusySlot: (store, busySlot: BusySlotDto) => {
				store.commit('addDisabledBusySlot', busySlot);
			},
			/** @function interface/clearDisabledBusySlots */
			clearDisabledBusySlots: (store) => {
				store.commit('clearDisabledBusySlots');
			},
			/** @function interface/setResourcesIds */
			setResourcesIds: (store, resourcesIds: number[]) => {
				store.commit('setResourcesIds', resourcesIds);
			},
			/** @function interface/deleteResourceId */
			deleteResourceId: (store, resourceId: number) => {
				store.commit('deleteResourceId', resourceId);
			},
			/** @function interface/setFilterMode */
			setFilterMode: (store, isFilterMode: boolean) => {
				store.commit('setFilterMode', isFilterMode);
			},
			/** @function interface/setIntersectionMode */
			setIntersectionMode: (store, isIntersectionForAll: boolean) => {
				store.commit('setIntersectionMode', isIntersectionForAll);
			},
			/** @function interface/setFilteredBookingsIds */
			setFilteredBookingsIds: (store, filteredBookingsIds: number[]) => {
				store.commit('setFilteredBookingsIds', filteredBookingsIds);
			},
			/** @function interface/setFilteredMarks */
			setFilteredMarks: (store, dates: number[]) => {
				store.commit('setFilteredMarks', dates);
			},
			/** @function interface/setFreeMarks */
			setFreeMarks: (store, dates: number[]) => {
				store.commit('setFreeMarks', dates);
			},
			/** @function interface/setTotalClients */
			setTotalClients: (store, totalClients: number) => {
				store.commit('setTotalClients', totalClients);
			},
			/** @function interface/setTotalNewClientsToday */
			setTotalNewClientsToday: (store, totalNewClientsToday: number) => {
				store.commit('setTotalNewClientsToday', totalNewClientsToday);
			},
			/** @function interface/setMoneyStatistics */
			setMoneyStatistics: (store, moneyStatistics: MoneyStatistics) => {
				store.commit('setMoneyStatistics', moneyStatistics);
			},
			setCounterMarks: (state, dates: number[]): void => {
				state.commit('setCounterMarks', dates);
			},
			/** @function interface/setIntersections */
			setIntersections: (store, intersections: Intersections) => {
				store.commit('setIntersections', intersections);
			},
			/** @function interface/hoverQuickFilter */
			hoverQuickFilter: (store, hour: number) => {
				store.commit('hoverQuickFilter', hour);
			},
			/** @function interface/fleeQuickFilter */
			fleeQuickFilter: (store, hour: number) => {
				store.commit('fleeQuickFilter', hour);
			},
			/** @function interface/activateQuickFilter */
			activateQuickFilter: (store, hour: number) => {
				store.commit('activateQuickFilter', hour);
				store.commit('clearQuickFilterIgnoredBookingIds');
			},
			/** @function interface/deactivateQuickFilter */
			deactivateQuickFilter: (store, hour: number) => {
				store.commit('deactivateQuickFilter', hour);
				store.commit('clearQuickFilterIgnoredBookingIds');
			},
			/** @function interface/addQuickFilterIgnoredBookingId */
			addQuickFilterIgnoredBookingId: (store, bookingId: number) => {
				store.commit('addQuickFilterIgnoredBookingId', bookingId);
			},
			/** @function interface/setMousePosition */
			setMousePosition: (store, mousePosition: MousePosition) => {
				store.commit('setMousePosition', mousePosition);
			},
			/** @function interface/setIsCurrentSenderAvailable */
			setIsCurrentSenderAvailable: (store, isCurrentSenderAvailable: boolean) => {
				store.commit('setIsCurrentSenderAvailable', isCurrentSenderAvailable);
			},
			/** @function interface/setIsFeatureEnabled */
			setIsFeatureEnabled: (store, isFeatureEnabled: boolean) => {
				store.commit('setIsFeatureEnabled', isFeatureEnabled);
			},
			/** @function interface/setCanTurnOnTrial */
			setCanTurnOnTrial: (store, canTurnOnTrial: boolean) => {
				store.commit('setCanTurnOnTrial', canTurnOnTrial);
			},
			/** @function interface/setIsShownTrialPopup */
			setIsShownTrialPopup: (store, isShownTrialPopup: boolean) => {
				store.commit('setIsShownTrialPopup', isShownTrialPopup);
			},
		};
	}

	// eslint-disable-next-line max-lines-per-function
	getMutations(): MutationTree<InterfaceModelState>
	{
		return {
			setEditingBookingId: (state, editingBookingId: number) => {
				state.editingBookingId = editingBookingId;
			},
			setDraggedBookingId: (state, draggedBookingId: number) => {
				state.draggedBookingId = draggedBookingId;
			},
			setResizedBookingId: (state, resizedBookingId: number) => {
				state.resizedBookingId = resizedBookingId;
			},
			setDraggedBookingResourceId: (state, draggedBookingResourceId: number) => {
				state.draggedBookingResourceId = draggedBookingResourceId;
			},
			setIsLoaded: (state, isLoaded: boolean) => {
				state.isLoaded = isLoaded;
			},
			setZoom: (state, zoom: number) => {
				state.zoom = zoom;
			},
			setExpanded: (state, expanded: boolean) => {
				state.expanded = expanded;
			},
			setScroll: (state, scroll: number) => {
				state.scroll = scroll;
			},
			setOffHoursHover: (state, offHoursHover: boolean) => {
				state.offHoursHover = offHoursHover;
			},
			setOffHoursExpanded: (state, offHoursExpanded: boolean) => {
				state.offHoursExpanded = offHoursExpanded;
			},
			setSelectedDateTs: (state, selectedDateTs: number) => {
				state.selectedDateTs = selectedDateTs;
			},
			setViewDateTs: (state, viewDateTs: number) => {
				state.viewDateTs = viewDateTs;
			},
			addDeletingBooking: (state, bookingId: number) => {
				state.deletingBookings[bookingId] = bookingId;
			},
			removeDeletingBooking: (state, bookingId: number) => {
				delete state.deletingBookings[bookingId];
			},
			addSelectedCell: (state, cell: CellDto) => {
				state.selectedCells[cell.id] = cell;
			},
			removeSelectedCell: (state, cell: CellDto) => {
				delete state.selectedCells[cell.id];
			},
			clearSelectedCells: (state) => {
				state.selectedCells = {};
			},
			setHoveredCell: (state, cell: CellDto | null) => {
				state.hoveredCell = cell;
			},
			upsertBusySlot: (state, busySlot: BusySlotDto): void => {
				state.busySlots[busySlot.id] ??= busySlot;
				Object.assign(state.busySlots[busySlot.id], busySlot);
			},
			clearBusySlots: (state) => {
				state.busySlots = {};
			},
			addDisabledBusySlot: (state, busySlot: BusySlotDto) => {
				state.disabledBusySlots[busySlot.id] = busySlot;
			},
			clearDisabledBusySlots: (state) => {
				state.disabledBusySlots = {};
			},
			setResourcesIds: (state, resourcesIds: number[]) => {
				state.resourcesIds = resourcesIds.sort((a, b) => a - b);
			},
			deleteResourceId: (state, resourceId: number) => {
				state.resourcesIds = state.resourcesIds.filter((id: number) => id !== resourceId);
			},
			setFilterMode: (state, isFilterMode: boolean) => {
				state.isFilterMode = isFilterMode;
			},
			setIntersectionMode: (state, isIntersectionForAll: boolean) => {
				state.isIntersectionForAll = isIntersectionForAll;
			},
			setFilteredBookingsIds: (state, filteredBookingsIds: number[]) => {
				state.filteredBookingsIds = [...filteredBookingsIds];
			},
			setFilteredMarks: (state, dates: number[]) => {
				state.filteredMarks = dates;
			},
			setFreeMarks: (state, dates: number[]) => {
				state.freeMarks = dates;
			},
			setTotalClients: (state, totalClients: number) => {
				state.totalClients = totalClients;
			},
			setTotalNewClientsToday: (state, totalNewClientsToday: number) => {
				state.totalNewClientsToday = totalNewClientsToday;
			},
			setMoneyStatistics: (state, moneyStatistics: MoneyStatistics) => {
				state.moneyStatistics = moneyStatistics;
			},
			setCounterMarks: (state, dates: number[]) => {
				state.counterMarks = dates;
			},
			setIntersections: (state, intersections: Intersections) => {
				state.intersections = intersections;
			},
			hoverQuickFilter: (state, hour: number) => {
				state.quickFilter.hovered[hour] = hour;
			},
			fleeQuickFilter: (state, hour: number) => {
				delete state.quickFilter.hovered[hour];
			},
			activateQuickFilter: (state, hour: number) => {
				state.quickFilter.active[hour] = hour;
			},
			deactivateQuickFilter: (state, hour: number) => {
				delete state.quickFilter.active[hour];
			},
			addQuickFilterIgnoredBookingId: (state, bookingId: number) => {
				state.quickFilter.ignoredBookingIds[bookingId] = bookingId;
			},
			clearQuickFilterIgnoredBookingIds: (state) => {
				state.quickFilter.ignoredBookingIds = {};
			},
			setMousePosition: (state, mousePosition: MousePosition) => {
				state.mousePosition = mousePosition;
			},
			setIsCurrentSenderAvailable: (state, isCurrentSenderAvailable: boolean) => {
				state.isCurrentSenderAvailable = isCurrentSenderAvailable;
			},
			setIsFeatureEnabled: (state, isFeatureEnabled: boolean) => {
				state.isFeatureEnabled = isFeatureEnabled;
			},
			setCanTurnOnTrial: (state, canTurnOnTrial: boolean) => {
				state.canTurnOnTrial = canTurnOnTrial;
			},
			setIsShownTrialPopup: (state, isShownTrialPopup: boolean) => {
				state.isShownTrialPopup = isShownTrialPopup;
			},
		};
	}
}
