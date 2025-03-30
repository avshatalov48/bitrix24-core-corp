import { createNamespacedHelpers } from 'ui.vue3.vuex';
import { Model } from 'booking.const';
import { busySlots } from 'booking.lib.busy-slots';
import { Drag } from 'booking.lib.drag';
import type { BookingModel } from 'booking.model.bookings';

import type { BookingUiDuration } from './booking/types';
import { Booking } from './booking/booking';
import { BusySlot } from './busy-slot/busy-slot';
import { Cell } from './cell/cell';
import { QuickFilterLine } from './quick-filter-line/quick-filter-line';
import './bookings.css';

const { mapGetters: mapInterfaceGetters } = createNamespacedHelpers(Model.Interface);

const MinUiBookingDurationMs = 15 * 60 * 1000;

export const Bookings = {
	name: 'Bookings',
	data(): { nowTs: number }
	{
		return {
			nowTs: Date.now(),
		};
	},
	computed: {
		...mapInterfaceGetters({
			resourcesIds: 'resourcesIds',
			selectedDateTs: 'selectedDateTs',
			isFilterMode: 'isFilterMode',
			filteredBookingsIds: 'filteredBookingsIds',
			selectedCells: 'selectedCells',
			hoveredCell: 'hoveredCell',
			busySlots: 'busySlots',
			quickFilter: 'quickFilter',
			isFeatureEnabled: 'isFeatureEnabled',
			editingBookingId: 'editingBookingId',
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
		bookings(): BookingModel[]
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
					.map((resourceId: number) => ({
						...booking,
						resourcesIds: [resourceId],
					}))
				;
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
	},
	mounted(): void
	{
		this.startInterval();

		if (this.isFeatureEnabled)
		{
			const dataId = this.editingBookingId ? `[data-id="${this.editingBookingId}"]` : '';

			this.dragManager = new Drag({
				container: this.$el.parentElement,
				draggable: `.booking-booking-booking${dataId}`,
			});
		}
	},
	beforeUnmount(): void
	{
		this.dragManager?.destroy();
	},
	methods: {
		generateBookingKey(booking: BookingModel): string
		{
			return `${booking.id}-${booking.resourcesIds[0]}`;
		},
		getUiBookings(resourceId: number): BookingUiDuration[]
		{
			return this.bookings
				.filter((booking) => booking.resourcesIds?.[0] === resourceId)
				.map((booking) => {
					const duration = booking.dateToTs - booking.dateFromTs;

					return {
						id: booking.id,
						fromTs: booking.dateFromTs,
						toTs: duration < MinUiBookingDurationMs
							? booking.dateFromTs + MinUiBookingDurationMs
							: booking.dateToTs
						,
					};
				});
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
	},
	components: {
		Booking,
		BusySlot,
		Cell,
		QuickFilterLine,
	},
	template: `
		<div class="booking-booking-bookings">
			<TransitionGroup name="booking-transition-booking">
				<template v-for="booking of bookings" :key="generateBookingKey(booking)">
					<Booking
						:bookingId="booking.id"
						:resourceId="booking.resourcesIds[0]"
						:nowTs
						:uiBookings="getUiBookings(booking.resourcesIds[0])"
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
