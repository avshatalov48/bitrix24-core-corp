import { createNamespacedHelpers } from 'ui.vue3.vuex';
import { Model } from 'booking.const';
import type { BookingModel } from 'booking.model.bookings';

import { busySlots } from '../../../lib/busy-slots/busy-slots';
import type { BookingUiDuration } from './booking/types';
import { Booking } from './booking/booking';
import { BusySlot } from './busy-slot/busy-slot';
import { Cell } from './cell/cell';
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
		}),
		bookingsHash(): string
		{
			return JSON.stringify(this.bookings);
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
		resourcesIds(): void
		{
			void busySlots.loadBusySlots();
		},
	},
	components: {
		Booking,
		BusySlot,
		Cell,
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
		</div>
	`,
};
