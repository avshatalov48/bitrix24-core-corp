import { createNamespacedHelpers } from 'ui.vue3.vuex';

import { Model } from 'booking.const';
import { busySlots } from 'booking.lib.busy-slots';
import type { BookingModel, BookingModelUi } from 'booking.model.bookings';

import type { BookingUiDuration } from './booking/types';
import { Booking } from './booking/booking';
import { BusySlot } from './busy-slot/busy-slot';
import { Cell } from './cell/cell';
import { QuickFilterLine } from './quick-filter-line/quick-filter-line';
import {
	createBookingModelUi,
	splitBookingsByResourceId,
	getResourceBookingUiGroups,
} from './libs';
import './bookings.css';

const { mapGetters: mapBookingsGetters } = createNamespacedHelpers(Model.Bookings);
const { mapGetters: mapInterfaceGetters } = createNamespacedHelpers(Model.Interface);
const { mapGetters: mapFilterGetters } = createNamespacedHelpers(Model.Filter);

export const Bookings = {
	name: 'Bookings',
	data(): { nowTs: number }
	{
		return {
			nowTs: Date.now(),
		};
	},
	computed: {
		...mapBookingsGetters({
			overbookingMap: 'overbookingMap',
		}),
		...mapInterfaceGetters({
			resourcesIds: 'resourcesIds',
			selectedDateTs: 'selectedDateTs',
			selectedCells: 'selectedCells',
			hoveredCell: 'hoveredCell',
			busySlots: 'busySlots',
			isFeatureEnabled: 'isFeatureEnabled',
			editingBookingId: 'editingBookingId',
			embedItems: 'embedItems',
			draggedBookingId: 'draggedBookingId',
		}),
		...mapFilterGetters({
			filteredBookingsIds: 'filteredBookingsIds',
			isFilterMode: 'isFilterMode',
			quickFilter: 'quickFilter',
		}),
		resourcesHash(): string
		{
			const resources = this.$store.getters[`${Model.Resources}/getByIds`](this.resourcesIds)
				.map(({ id, slotRanges }) => ({ id, slotRanges }))
			;

			return JSON.stringify(resources);
		},
		bookingsHash(): string
		{
			const bookings = this.bookings
				.map(({ id, dateFromTs, dateToTs }) => ({ id, dateFromTs, dateToTs }))
			;

			return JSON.stringify(bookings);
		},
		bookings(): BookingModelUi[]
		{
			const dateTs = this.selectedDateTs;

			let bookings = [];
			if (this.isFilterMode)
			{
				bookings = this.$store.getters[`${Model.Bookings}/getByDateAndIds`](dateTs, this.filteredBookingsIds);
			}
			else
			{
				bookings = this.$store.getters[`${Model.Bookings}/getByDateAndResources`](dateTs, this.resourcesIds);
			}

			return bookings.flatMap((booking) => {
				return booking.resourcesIds
					.filter((resourceId: number) => this.resourcesIds.includes(resourceId))
					.map((resourceId: number) => {
						return createBookingModelUi(resourceId, booking, this.overbookingMap.get(booking.id));
					});
			}).sort((a, b) => {
				if (a.resourcesIds[0] !== b.resourcesIds[0])
				{
					return b.resourcesIds[0] - a.resourcesIds[0];
				}

				if (a.dateFromTs !== b.dateFromTs)
				{
					return a.dateFromTs - b.dateFromTs;
				}

				return b.overbooking - a.overbooking;
			});
		},
		cells(): CellDto[]
		{
			const cells = [...Object.values(this.selectedCells), this.hoveredCell];
			const dateFromTs = this.selectedDateTs;
			const dateToTs = new Date(dateFromTs).setDate(new Date(dateFromTs).getDate() + 1);

			return cells.filter((cell: CellDto) => cell && cell.toTs > dateFromTs && dateToTs > cell.fromTs);
		},
		quickFilterHours(): number[]
		{
			const activeHours = new Set(Object.values(this.quickFilter.active));

			return Object.values(this.quickFilter.hovered).filter((hour) => !activeHours.has(hour));
		},
		resourceBookings(): Map<number, BookingModelUi>
		{
			return splitBookingsByResourceId(this.bookings);
		},
		resourceBookingsUiGroupsMap(): Map<number, BookingUiDuration[]>
		{
			return getResourceBookingUiGroups(this.resourceBookings);
		},
		embedEditingMode(): boolean
		{
			return (
				this.isFeatureEnabled
				&& (
					this.editingBookingId > 0
					|| (this.embedItems?.length ?? 0) > 0
				)
			);
		},
		draggedBooking(): BookingModel | null
		{
			if (!this.draggedBookingId)
			{
				return null;
			}

			return this.bookings.find(({ id }) => id === this.draggedBookingId) || null;
		},
	},
	mounted(): void
	{
		this.startInterval();
	},
	methods: {
		generateBookingKey(booking: BookingModel): string
		{
			return `${booking.id}-${booking.resourcesIds[0]}`;
		},
		getBookingUiGroupsByResourceId(resourceId: number): BookingUiDuration[]
		{
			return this.resourceBookingsUiGroupsMap.get(resourceId) || [];
		},
		startInterval(): void
		{
			setInterval(() => {
				this.nowTs = Date.now();
			}, 5 * 1000);
		},
	},
	watch: {
		selectedDateTs(): void
		{
			void busySlots.loadBusySlots();
		},
		bookingsHash(): void
		{
			void busySlots.loadBusySlots();
		},
		resourcesHash(): void
		{
			void busySlots.loadBusySlots();
		},
		overbookingMap(): void
		{
			void busySlots.loadBusySlots();
		},
	},
	components: {
		Booking,
		BusySlot,
		Cell,
		QuickFilterLine,
	},
	template: `
		<div
			class="booking-booking-bookings"
			:class="{
				'embed-editing-mode': embedEditingMode,
			}"
		>
			<TransitionGroup name="booking-transition-booking">
				<template v-for="booking of bookings" :key="generateBookingKey(booking)">
					<Booking
						:bookingId="booking.id"
						:resourceId="booking.resourcesIds[0]"
						:nowTs
						:bookingUiGroups="getBookingUiGroupsByResourceId(booking.resourcesIds[0])"
					/>
				</template>
			</TransitionGroup>
			<template v-for="busySlot of busySlots" :key="busySlot.id">
				<BusySlot
					:busySlot="busySlot"
				/>
			</template>
			<template v-for="cell of cells" :key="cell.id">
				<Cell
					:cell="cell"
					:draggedBooking="draggedBooking"
				/>
			</template>
			<template v-for="hour of quickFilterHours" :key="hour">
				<QuickFilterLine
					:hour="hour"
				/>
			</template>
		</div>
	`,
};
