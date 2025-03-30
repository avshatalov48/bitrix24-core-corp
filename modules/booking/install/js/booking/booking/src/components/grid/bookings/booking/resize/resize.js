import { Dom, Event } from 'main.core';
import { Model } from 'booking.const';
import { Duration } from 'booking.lib.duration';
import { isRealId } from 'booking.lib.is-real-id';
import { busySlots } from 'booking.lib.busy-slots';
import { grid } from 'booking.lib.grid';
import { bookingService } from 'booking.provider.service.booking-service';
import type { BookingModel } from 'booking.model.bookings';

import './resize.css';

const ResizeDirection = Object.freeze({
	From: -1,
	None: 0,
	To: 1,
});

const minDuration = Duration.getUnitDurations().i * 5;
const minInitialDuration = Duration.getUnitDurations().i * 15;

export const Resize = {
	props: {
		bookingId: {
			type: [Number, String],
			required: true,
		},
		resourceId: {
			type: Number,
			required: true,
		},
	},
	data(): Object {
		return {
			resizeDirection: ResizeDirection.None,
			resizeFromTs: null,
			resizeToTs: null,
		};
	},
	computed: {
		booking(): BookingModel
		{
			return this.$store.getters[`${Model.Bookings}/getById`](this.bookingId);
		},
		initialHeight(): number
		{
			return grid.calculateHeight(this.booking.dateFromTs, this.booking.dateToTs);
		},
		initialDuration(): number
		{
			return Math.max(this.booking.dateToTs - this.booking.dateFromTs, minInitialDuration);
		},
		dateFromTsRounded(): number
		{
			return this.roundTimestamp(this.resizeFromTs);
		},
		dateToTsRounded(): number
		{
			return this.roundTimestamp(this.resizeToTs);
		},
		closestOnFrom(): number
		{
			return this.colliding.reduce((closest, { toTs }) => {
				return (closest < toTs && toTs <= this.booking.dateFromTs) ? toTs : closest;
			}, 0);
		},
		closestOnTo(): number
		{
			return this.colliding.reduce((closest, { fromTs }) => {
				return (this.booking.dateToTs <= fromTs && fromTs < closest) ? fromTs : closest;
			}, Infinity);
		},
		colliding(): {fromTs: number, toTs: number}[]
		{
			return this.$store.getters[`${Model.Interface}/getColliding`](this.resourceId, [this.bookingId]);
		},
	},
	methods: {
		onMouseDown(event: MouseEvent): void
		{
			const direction = Dom.hasClass(event.target, '--from') ? ResizeDirection.From : ResizeDirection.To;

			void this.startResize(direction);
		},
		async startResize(direction: number = ResizeDirection.To): Promise<void>
		{
			Dom.style(document.body, 'user-select', 'none');
			Event.bind(window, 'mouseup', this.endResize);
			Event.bind(window, 'pointermove', this.resize);
			this.resizeDirection = direction;

			void this.updateIds(this.bookingId, this.resourceId);
		},
		resize(event: MouseEvent): void
		{
			if (!this.resizeDirection)
			{
				return;
			}

			const resizeHeight = this.resizeDirection === ResizeDirection.To
				? (event.clientY - this.$el.getBoundingClientRect().top)
				: (this.$el.getBoundingClientRect().bottom - event.clientY)
			;
			const duration = resizeHeight * this.initialDuration / this.initialHeight;
			const newDuration = Math.max(duration, minDuration);

			if (this.resizeDirection === ResizeDirection.To)
			{
				this.resizeFromTs = this.booking.dateFromTs;
				this.resizeToTs = Math.min(this.booking.dateFromTs + newDuration, this.closestOnTo);
			}
			else
			{
				this.resizeFromTs = Math.max(this.booking.dateToTs - newDuration, this.closestOnFrom);
				this.resizeToTs = this.booking.dateToTs;
			}

			this.$emit('update', this.resizeFromTs, this.resizeToTs);
		},
		async endResize(): Promise<void>
		{
			this.resizeBooking();

			Dom.style(document.body, 'user-select', '');
			Event.unbind(window, 'mouseup', this.endResize);
			Event.unbind(window, 'pointermove', this.resize);
			this.$emit('update', null, null);

			void this.updateIds(null, null);
		},
		async updateIds(bookingId: number, resourceId: number): Promise<void>
		{
			await Promise.all([
				this.$store.dispatch(`${Model.Interface}/setResizedBookingId`, bookingId),
				this.$store.dispatch(`${Model.Interface}/setDraggedBookingResourceId`, resourceId),
			]);

			void busySlots.loadBusySlots();
		},
		resizeBooking(): void
		{
			if (!this.dateFromTsRounded || !this.dateToTsRounded)
			{
				return;
			}

			if (this.dateFromTsRounded === this.booking.dateFromTs && this.dateToTsRounded === this.booking.dateToTs)
			{
				return;
			}

			const id = this.bookingId;
			const booking = {
				id,
				dateFromTs: this.dateFromTsRounded,
				dateToTs: this.dateToTsRounded,
				timezoneFrom: this.booking.timezoneFrom,
				timezoneTo: this.booking.timezoneTo,
			};

			if (!isRealId(this.bookingId))
			{
				void this.$store.dispatch(`${Model.Bookings}/update`, { id, booking });

				return;
			}

			void bookingService.update({
				id,
				...booking,
			});
		},
		roundTimestamp(timestamp: number): void
		{
			const fiveMinutes = Duration.getUnitDurations().i * 5;

			return Math.round(timestamp / fiveMinutes) * fiveMinutes;
		},
	},
	template: `
		<div>
			<div class="booking-booking-resize --from" @mousedown="onMouseDown"></div>
			<div class="booking-booking-resize --to" @mousedown="onMouseDown"></div>
		</div>
	`,
};
