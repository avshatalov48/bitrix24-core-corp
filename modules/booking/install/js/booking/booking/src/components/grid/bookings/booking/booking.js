import { Event, Type } from 'main.core';
import { mapGetters } from 'ui.vue3.vuex';

import { Model, DraggedElementKind } from 'booking.const';
import { isRealId } from 'booking.lib.is-real-id';
import { BookingAnalytics } from 'booking.lib.analytics';
import { bookingService } from 'booking.provider.service.booking-service';
import type {
	BookingModel,
	OverbookingMapItem,
	OverbookingResourceIntersections,
} from 'booking.model.bookings';
import type { Occupancy, DraggedDataTransfer } from 'booking.model.interface';

import { Actions } from './actions/actions';
import { BookingBase } from './booking-base';
import './booking.css';
import {
	countBookingLeftOffset,
	countBookingWidth,
	getOverbookingFreeSpace,
	findTimeForDroppedBooking,
} from './lib';
import type {
	BookingUiDuration,
	OverlappingBookings,
} from './types';
import type { ActionsPopupOptions } from './actions/actions';

export type { BookingUiDuration };

// @vue/component
export const Booking = {
	name: 'Booking',
	components: {
		BookingBase,
		Actions,
	},
	props: {
		bookingId: {
			type: [Number, String],
			required: true,
		},
		resourceId: {
			type: Number,
			required: true,
		},
		nowTs: {
			type: Number,
			required: true,
		},
		/**
		 * @param {BookingUiGroup[]} bookingUiGroups
		 */
		bookingUiGroups: {
			type: Array,
			default: () => [],
		},
	},
	data(): { dropArea: boolean, freeSpace: ?Occupancy }
	{
		return {
			dropArea: false,
			freeSpace: null,
		};
	},
	computed: {
		...mapGetters({
			getBookingById: `${Model.Bookings}/getById`,
			overbookingMap: `${Model.Bookings}/overbookingMap`,
			selectedDateTs: `${Model.Interface}/selectedDateTs`,
			deletingBookings: `${Model.Interface}/deletingBookings`,
			animationPause: `${Model.Interface}/animationPause`,
			isBookingCreatedFromEmbed: `${Model.Interface}/isBookingCreatedFromEmbed`,
			editingBookingId: `${Model.Interface}/editingBookingId`,
			draggedDataTransfer: `${Model.Interface}/draggedDataTransfer`,
			draggedBookingId: `${Model.Interface}/draggedBookingId`,
			getWaitListItemById: `${Model.WaitList}/getById`,
			getResourceById: `${Model.Resources}/getById`,
			isDeletingResourceFilterMode: `${Model.Filter}/isDeletingResourceFilterMode`,
			deletingResource: `${Model.Filter}/deletingResource`,
		}),
		booking(): BookingModel
		{
			return this.getBookingById(this.bookingId);
		},
		deletingBookings(): number[]
		{
			return Object.values(this.$store.getters[`${Model.Interface}/deletingBookings`]);
		},
		overbooking(): OverbookingMapItem | null
		{
			return this.overbookingMap.get(this.bookingId) || null;
		},
		overbookingInResource(): OverbookingResourceIntersections | null
		{
			return this.overbooking?.items
				?.find((item) => item.resourceId === this.resourceId) || null;
		},
		hasOverbooking(): boolean
		{
			return (this.overbookingInResource?.intersections || [])
				.some(({ id }) => !this.deletingBookings.includes(id));
		},
		isShifted(): boolean
		{
			return (
				this.hasOverbooking
				&& Type.isPlainObject(this.overbookingInResource)
				&& this.overbookingInResource.shifted
			);
		},
		overlappingBookings(): OverlappingBookings
		{
			const bookingId = !this.isShifted || !this.hasOverbooking
				? this.bookingId
				: this.overbookingDependencies[0];

			return this.bookingUiGroups.find(({ bookingIds }) => bookingIds.includes((bookingId)))?.bookingIds || [];
		},
		overbookingDependencies(): number[]
		{
			return (this.overbookingInResource?.intersections || []).map(({ id }) => id);
		},
		bookingWidth(): number
		{
			if (this.hasOverbooking)
			{
				return countBookingWidth(this.overlappingBookings) / 2;
			}

			return countBookingWidth(this.overlappingBookings);
		},
		leftOffset(): number
		{
			if (this.isShifted)
			{
				const bookingId = this.overbookingDependencies[0];
				const leftOffset = countBookingLeftOffset({
					bookingId,
					bookingWidth: this.bookingWidth,
					overlappingBookings: this.overlappingBookings,
				});

				return leftOffset * 2 + this.bookingWidth;
			}

			return countBookingLeftOffset({
				bookingId: this.booking.id,
				bookingWidth: this.hasOverbooking ? this.bookingWidth * 2 : this.bookingWidth,
				overlappingBookings: this.overlappingBookings,
			});
		},
		actionsPopupOptions(): ActionsPopupOptions
		{
			return {
				overbooking: {
					disabled: this.overbooking !== null,
					hidden: this.isDeletedResource,
				},
				waitList: {
					hidden: this.isDeletedResource,
				},
			};
		},
		realBooking(): boolean
		{
			return Type.isNumber(this.bookingId);
		},
		hasAccent(): boolean
		{
			return this.editingBookingId === this.bookingId || this.isBookingCreatedFromEmbed(this.bookingId);
		},
		isDeletedResource(): boolean
		{
			return this.getResourceById(this.resourceId)?.isDeleted ?? false;
		},
		isShaded(): boolean
		{
			return (
				this.isDeletingResourceFilterMode
				&& this.resourceId !== this.deletingResource.id
			);
		},
	},
	watch: {
		draggedDataTransfer: {
			handler(draggedDataTransfer: DraggedDataTransfer): void
			{
				if (this.hasOverbooking)
				{
					return;
				}

				if (draggedDataTransfer.kind === DraggedElementKind.Booking && draggedDataTransfer.id === this.bookingId)
				{
					return;
				}

				if (!draggedDataTransfer || !draggedDataTransfer.kind || !draggedDataTransfer.id)
				{
					this.stopDropHandler();
				}
				else
				{
					this.startDropHandler();
				}
			},
			deep: true,
		},
	},
	methods: {
		dragMouseEnter(): void
		{
			if (this.isDeletedResource)
			{
				this.dropArea = false;

				return;
			}

			if (this.dropArea || !this.draggedDataTransfer.id)
			{
				return;
			}

			if (this.draggedDataTransfer.kind === DraggedElementKind.WaitListItem)
			{
				this.freeSpace = {
					fromTs: this.booking.dateFromTs,
					toTs: this.booking.dateToTs,
					resourcesIds: this.booking.resourcesIds,
				};
				this.dropArea = true;

				return;
			}

			const draggedBookingId = this.draggedBookingId;
			if (draggedBookingId === null)
			{
				return;
			}

			const draggedBooking = this.getBookingById(draggedBookingId);
			const bookingDuration = this.booking.dateToTs - this.booking.dateFromTs;
			const draggedBookingDuration = draggedBooking.dateToTs - draggedBooking.dateFromTs;
			if (bookingDuration >= draggedBookingDuration && draggedBooking.resourcesIds.length <= 1)
			{
				this.freeSpace = {
					fromTs: this.booking.dateFromTs,
					toTs: this.booking.dateToTs,
					resourcesIds: this.booking.resourcesIds,
				};
				this.dropArea = true;

				return;
			}

			const excludeBookingFn = (booking: BookingModel): boolean => {
				if (booking.id === this.draggedBookingId)
				{
					return true;
				}

				const overbooking = this.overbookingMap.get(booking.id);
				const resourceId = this.resourceId;

				return !overbooking || overbooking.items.some((item) => item.resourceId === resourceId);
			};
			const colliding = this.$store.getters[`${Model.Interface}/getColliding`](
				this.resourceId,
				excludeBookingFn,
			);
			if (colliding.length === 0)
			{
				this.freeSpace = {
					fromTs: this.booking.dateFromTs,
					toTs: this.booking.dateToTs,
					resourcesIds: this.booking.resourcesIds,
				};
				this.dropArea = true;

				return;
			}

			const freeSpace = getOverbookingFreeSpace({
				booking: this.booking,
				colliding,
				selectedDateTs: this.selectedDateTs,
				draggedBookingResourcesIds: draggedBooking.resourcesIds,
			});
			this.freeSpace = freeSpace;
			this.dropArea = freeSpace && (freeSpace.toTs - freeSpace.fromTs) >= draggedBookingDuration;
		},
		dragMouseLeave(): void
		{
			this.dropArea = false;
			this.freeSpace = null;
		},
		async dropElement(): void
		{
			const id = this.draggedDataTransfer.id;
			if (!id || !this.freeSpace)
			{
				return;
			}

			if (this.draggedDataTransfer.kind === DraggedElementKind.Booking)
			{
				await this.dropBooking(id);

				return;
			}

			if (this.draggedDataTransfer.kind === DraggedElementKind.WaitListItem)
			{
				await this.dropWaitListItem(id);
			}
		},
		async dropBooking(id: number | string): Promise<void>
		{
			const droppedBooking = this.getBookingById(id);
			const { dateFromTs, dateToTs } = findTimeForDroppedBooking(this.freeSpace, this.booking, droppedBooking);
			const overbooking = {
				id,
				dateFromTs,
				dateToTs,
				timezoneFrom: droppedBooking.timezoneFrom,
				timezoneTo: droppedBooking.timezoneTo,
				resourcesIds: [
					...new Set([
						this.resourceId,
						...droppedBooking.resourcesIds.slice(1, droppedBooking.resourcesIds.length),
					]),
				],
			};

			if (!isRealId(id))
			{
				await this.$store.dispatch(`${Model.Bookings}/update`, { id, booking: overbooking });

				return;
			}

			await bookingService.update({
				id,
				...overbooking,
			});
		},
		async dropWaitListItem(id: number): Promise<void>
		{
			const droppedWaitListItem = this.getWaitListItemById(id);
			const clients = [...droppedWaitListItem.clients];
			const resource = this.getResourceById(this.resourceId);
			const timezone = resource?.slotRanges?.[0]?.timezone;

			const overbooking: BookingModel = {
				id: `wl${id}`,
				resourcesIds: [this.resourceId],
				name: droppedWaitListItem.name,
				note: droppedWaitListItem.note,
				clients,
				primaryClient: clients.length > 0 ? clients[0] : undefined,
				externalData: [...droppedWaitListItem.externalData],
				dateFromTs: this.booking.dateFromTs,
				dateToTs: this.booking.dateToTs,
				timezoneFrom: timezone,
				timezoneTo: timezone,
			};
			const result = await bookingService.createFromWaitListItem(id, overbooking);

			if (result.success && result.booking)
			{
				BookingAnalytics.sendAddBooking({ isOverbooking: true });
			}
		},
		startDropHandler(): void
		{
			Event.bind(this.$el, 'mousemove', this.dragMouseEnter, { capture: true });
			Event.bind(this.$el, 'mouseleave', this.dragMouseLeave, { capture: true });
			Event.bind(this.$el, 'mouseup', this.dropElement, { capture: true });
		},
		stopDropHandler(): void
		{
			this.dropArea = false;
			this.freeSpace = null;
			Event.unbind(this.$el, 'mousemove', this.dragMouseEnter, { capture: true });
			Event.unbind(this.$el, 'mouseleave', this.dragMouseLeave, { capture: true });
			Event.unbind(this.$el, 'mouseup', this.dropElement, { capture: true });
		},
	},
	template: `
		<BookingBase
			:bookingId
			:resourceId
			:nowTs
			:leftOffset
			:bookingClass="{
				'--short': overlappingBookings.length > 1,
				'--overbooking': hasOverbooking,
				'--overbooking-bg': hasOverbooking || overbooking !== null,
				'--shifted': isShifted && !realBooking,
				'--drop-area': dropArea,
				'--accent': hasAccent,
				'--shaded': isShaded,
				'not-transition': animationPause,
			}"
			:width="bookingWidth"
		>
			<template #actions>
				<Actions
					:bookingId
					:resourceId
					:actionsPopupOptions
				/>
			</template>
			<div class="booking--booking-pseudo-overbooking"></div>
		</BookingBase>
	`,
};
