import { Dom, Event } from 'main.core';
import { Popup, PopupOptions } from 'main.popup';
import { mapGetters } from 'ui.vue3.vuex';

import { Model } from 'booking.const';
import { Duration } from 'booking.lib.duration';
import { isRealId } from 'booking.lib.is-real-id';
import { busySlots } from 'booking.lib.busy-slots';
import { grid } from 'booking.lib.grid';
import { bookingService } from 'booking.provider.service.booking-service';
import type { BookingModel, OverbookingMap } from 'booking.model.bookings';

import './resize.css';

const ResizeDirection = Object.freeze({
	From: -1,
	None: 0,
	To: 1,
});

const minDuration = Duration.getUnitDurations().i * 5;
const minInitialDuration = Duration.getUnitDurations().i * 15;

// @vue/component
export const Resize = {
	name: 'BookingResize',
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
	setup(): { tooltipTop: Popup | null, tooltipBottom: Popup | null }
	{
		const tooltipTop = null;
		const tooltipBottom = null;

		return {
			tooltipTop,
			tooltipBottom,
		};
	},
	data(): Object
	{
		return {
			resizeDirection: ResizeDirection.None,
			resizeFromTs: null,
			resizeToTs: null,
		};
	},
	computed: {
		...mapGetters({
			selectedDateTs: `${Model.Interface}/selectedDateTs`,
			overbookingMap: `${Model.Bookings}/overbookingMap`,
		}),
		booking(): BookingModel
		{
			return this.$store.getters[`${Model.Bookings}/getById`](this.bookingId);
		},
		limits(): { fromTs: number, toTs: number }
		{
			return {
				fromTs: new Date(this.selectedDateTs).setHours(0, 0, 0, 0),
				toTs: new Date(this.selectedDateTs).setHours(24, 0, 0, 0),
			};
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
		excludeBookings(): number[] | (booking: BookingModel) => boolean
		{
			// return when overbooking option disabled
			// 	return [this.bookingId];
			const overbookingMap: OverbookingMap = this.overbookingMap;

			return (booking: BookingModel) => {
				if (booking.id === this.bookingId)
				{
					return true;
				}

				const overbooking = overbookingMap.get(booking.id);
				const resourcesIds = this.booking.resourcesIds;

				return !overbooking || overbooking.items.every((item) => !resourcesIds.includes(item.resourceId));
			};
		},
		colliding(): { fromTs: number, toTs: number }[]
		{
			return this.$store.getters[`${Model.Interface}/getColliding`](
				this.booking.resourcesIds,
				this.excludeBookings,
			);
		},
		hasIntersections(): boolean
		{
			return this.booking.resourcesIds.length > 1;
		},
	},
	methods: {
		createPopup(options: Partial<PopupOptions>): Popup
		{
			return new Popup({
				autoHide: true,
				cacheable: true,
				darkMode: true,
				...options,
			});
		},
		showTooltipTop(options: Partial<PopupOptions>): void
		{
			if (!this.tooltipTop)
			{
				this.tooltipTop = this.createPopup({
					id: `resize-top-${this.bookingId}-${this.resourceId}`,
					bindElement: this.$refs.bookingResizeTop,
					bindOptions: {
						position: 'bottom',
					},
					offsetLeft: this.$refs.bookingResizeTop.offsetWidth / 2,
					content: this.loc('BOOKING_RESIZE_GRID_LIMIT'),
					...options,
				});
			}
			else if (options?.content)
			{
				this.tooltipTop.setContent(options.content);
			}

			this.tooltipTop.show();
		},
		showTooltipBottom(options: Partial<PopupOptions>): void
		{
			if (!this.tooltipBottom)
			{
				this.tooltipBottom = this.createPopup({
					id: `resize-bottom-${this.bookingId}-${this.resourceId}`,
					bindElement: this.$refs.bookingResizeBottom,
					bindOptions: {
						position: 'bottom',
					},
					offsetLeft: this.$refs.bookingResizeBottom.offsetWidth / 2,
					content: this.loc('BOOKING_RESIZE_GRID_LIMIT'),
					...options,
				});
			}
			else if (options?.content)
			{
				this.tooltipBottom.setContent(options.content);
			}

			this.tooltipBottom.show();
		},
		hideTooltips(): void
		{
			this.tooltipTop?.close?.();
			this.tooltipBottom?.close?.();
		},
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
				const resizeToTs = this.booking.dateFromTs + newDuration;
				const toTs = Math.min(resizeToTs, this.limits.toTs);

				this.manageToLimitNotification(resizeToTs, toTs);

				this.resizeFromTs = this.booking.dateFromTs;
				this.resizeToTs = Math.min(toTs, this.closestOnTo);
			}
			else
			{
				const resizeFromTs = this.booking.dateToTs - newDuration;
				const fromTs = Math.max(resizeFromTs, this.limits.fromTs);

				this.manageFromLimitNotification(resizeFromTs, fromTs);

				this.resizeFromTs = Math.max(fromTs, this.closestOnFrom);
				this.resizeToTs = this.booking.dateToTs;
			}

			this.$emit('update', this.resizeFromTs, this.resizeToTs);
		},
		async endResize(): Promise<void>
		{
			this.resizeBooking();
			this.hideTooltips();

			Dom.style(document.body, 'user-select', '');
			Event.unbind(window, 'mouseup', this.endResize);
			Event.unbind(window, 'pointermove', this.resize);
			this.$emit('update', null, null);

			await this.updateIds(null, null);
		},
		manageFromLimitNotification(resizeFromTs: number, fromTs: number): void
		{
			const colliding = this.colliding.filter(({ toTs }) => toTs === this.closestOnFrom);

			if (resizeFromTs < this.limits.fromTs)
			{
				this.showTooltipTop({
					content: this.loc('BOOKING_RESIZE_GRID_LIMIT'),
				});

				return;
			}

			if (fromTs < this.closestOnFrom)
			{
				if (this.checkClosestByIntersection(colliding))
				{
					this.showTooltipTop({
						content: this.loc('BOOKING_RESIZE_INTERSECTIONS_LIMIT'),
					});

					return;
				}

				this.showTooltipTop({
					content: this.loc('BOOKING_RESIZE_LIMIT'),
				});

				return;
			}

			this.hideTooltips();
		},
		manageToLimitNotification(resizeToTs: number, toTs: number): void
		{
			const colliding = this.colliding.filter(({ fromTs }) => fromTs === this.closestOnTo);

			if (resizeToTs > this.limits.toTs)
			{
				this.showTooltipBottom({
					content: this.loc('BOOKING_RESIZE_GRID_LIMIT'),
				});

				return;
			}

			if (toTs > this.closestOnTo)
			{
				if (this.checkClosestByIntersection(colliding))
				{
					this.showTooltipBottom({
						content: this.loc('BOOKING_RESIZE_INTERSECTIONS_LIMIT'),
					});

					return;
				}

				this.showTooltipBottom({
					content: this.loc('BOOKING_RESIZE_LIMIT'),
				});

				return;
			}

			this.hideTooltips();
		},
		checkClosestByIntersection(colliding): boolean
		{
			return (
				this.hasIntersections
				&& (
					colliding.length === 1
					|| colliding.filter((coll) => coll.resourcesIds[0] !== this.resourceId).length >= 2
				)
			);
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

			const resource = this.$store.getters[`${Model.Resources}/getById`](this.resourceId);
			if (resource.isDeleted)
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
			<div ref="bookingResizeTop" class="booking-booking-resize --from" @mousedown="onMouseDown"></div>
			<div ref="bookingResizeBottom" class="booking-booking-resize --to" @mousedown="onMouseDown"></div>
		</div>
	`,
};
