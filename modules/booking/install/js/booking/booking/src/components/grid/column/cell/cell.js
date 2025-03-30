import { mapGetters } from 'ui.vue3.vuex';
import { Model } from 'booking.const';
import type { BookingModel } from 'booking.model.bookings';

import type { CellDto, CellData } from './types';
import './cell.css';

const halfHour = 30 * 60 * 1000;

export const Cell = {
	name: 'Cell',
	props: {
		/** @type {CellDto} */
		cell: {
			type: Object,
			required: true,
		},
	},
	data(): CellData
	{
		return {
			halfOffset: 0,
		};
	},
	computed: {
		...mapGetters({
			isFilterMode: `${Model.Interface}/isFilterMode`,
			isEditingBookingMode: `${Model.Interface}/isEditingBookingMode`,
			draggedBookingId: `${Model.Interface}/draggedBookingId`,
			resizedBookingId: `${Model.Interface}/resizedBookingId`,
			quickFilter: `${Model.Interface}/quickFilter`,
		}),
		isAvailable(): boolean
		{
			if (this.isFilterMode || this.resizedBookingId || (this.isEditingBookingMode && !this.draggedBookingId))
			{
				return false;
			}

			const { fromTs, toTs } = this.freeSpace;

			const cellFromTs = this.cell.fromTs;
			const cellHalfTs = this.cell.fromTs + halfHour;

			return (toTs > cellFromTs || toTs > cellHalfTs) && (toTs - fromTs) >= this.duration;
		},
		fromTs(): number
		{
			return Math.min(this.freeSpace.toTs - this.duration, this.cell.fromTs) + this.halfOffset;
		},
		toTs(): number
		{
			return this.fromTs + this.duration;
		},
		duration(): number
		{
			if (this.draggedBooking)
			{
				return this.draggedBooking.dateToTs - this.draggedBooking.dateFromTs;
			}

			return this.cell.toTs - this.cell.fromTs;
		},
		draggedBooking(): BookingModel
		{
			return this.$store.getters[`${Model.Bookings}/getById`](this.draggedBookingId) ?? null;
		},
		freeSpace(): {fromTs: number, toTs: number}
		{
			let maxFrom = 0;
			let minTo = Infinity;
			for (const { fromTs, toTs } of this.colliding)
			{
				if (this.cell.fromTs + halfHour > fromTs && this.cell.fromTs + halfHour < toTs)
				{
					maxFrom = toTs;
					minTo = fromTs;
					break;
				}

				if (toTs <= this.cell.fromTs + halfHour)
				{
					maxFrom = Math.max(maxFrom, toTs);
				}

				if (fromTs >= this.cell.fromTs + halfHour)
				{
					minTo = Math.min(minTo, fromTs);
				}
			}

			return { fromTs: maxFrom, toTs: minTo };
		},
		colliding(): {fromTs: number, toTs: number}[]
		{
			return this.$store.getters[`${Model.Interface}/getColliding`](this.cell.resourceId, [this.draggedBookingId]);
		},
		quickFilterHovered(): boolean
		{
			return (this.cell.minutes / 60) in this.quickFilter.hovered;
		},
		quickFilterActive(): boolean
		{
			return (this.cell.minutes / 60) in this.quickFilter.active;
		},
	},
	methods: {
		mouseEnterHandler(event: MouseEvent): void
		{
			this.updateHalfHour(event);
		},
		mouseLeaveHandler(event: MouseEvent): void
		{
			const nextHoveredCell = event.relatedTarget?.closest('.booking-booking-base-cell');

			if (!nextHoveredCell || nextHoveredCell?.dataset?.selected === 'true')
			{
				void this.$store.dispatch(`${Model.Interface}/setHoveredCell`, null);
			}
		},
		mouseMoveHandler(event: MouseEvent): void
		{
			this.updateHalfHour(event);
		},
		updateHalfHour(event: MouseEvent): void
		{
			if (this.$refs.button?.contains(event.target))
			{
				return;
			}

			this.halfOffset = 0;
			const clientY = event.clientY - window.scrollY;
			const rect = this.$el.getBoundingClientRect();
			const bottomHalf = clientY > (rect.top + rect.top + rect.height) / 2;
			const canSubtractHalfHour = this.fromTs >= this.freeSpace.fromTs;
			const canAddHalfHour = this.toTs + halfHour <= this.freeSpace.toTs;

			if ((bottomHalf && canAddHalfHour) || (!bottomHalf && !canSubtractHalfHour))
			{
				this.halfOffset = halfHour;
			}

			if (!bottomHalf && !canSubtractHalfHour && this.freeSpace.fromTs - this.cell.fromTs > 0)
			{
				this.halfOffset = this.freeSpace.fromTs - this.cell.fromTs;
			}

			if ((!bottomHalf && canSubtractHalfHour) || (bottomHalf && !canAddHalfHour))
			{
				this.halfOffset = 0;
			}

			const offsetNotMatchesHalf = bottomHalf === (this.halfOffset === 0);

			if (this.duration <= halfHour && offsetNotMatchesHalf)
			{
				this.clearCell(event);

				return;
			}

			this.hoverCell({
				id: `${this.cell.resourceId}-${this.fromTs}-${this.toTs}`,
				fromTs: this.fromTs,
				toTs: this.toTs,
				resourceId: this.cell.resourceId,
				boundedToBottom: this.toTs === this.freeSpace.toTs,
			});
		},
		clearCell(event: MouseEvent): void
		{
			const nextHoveredCell = event.relatedTarget?.closest('.booking-booking-base-cell');

			if (!nextHoveredCell || nextHoveredCell?.dataset?.selected === 'true')
			{
				void this.$store.dispatch(`${Model.Interface}/setHoveredCell`, null);
			}
		},
		hoverCell(cell: CellDto): void
		{
			void this.$store.dispatch(`${Model.Interface}/setHoveredCell`, null);

			if (this.isAvailable)
			{
				void this.$store.dispatch(`${Model.Interface}/setHoveredCell`, cell);
			}
		},
	},
	watch: {
		draggedBookingId(): void
		{
			if (!this.draggedBookingId)
			{
				void this.$store.dispatch(`${Model.Interface}/setHoveredCell`, null);
			}
		},
	},
	template: `
		<div
			class="booking-booking-grid-cell"
			:class="{
				'--quick-filter-hovered': quickFilterHovered,
				'--quick-filter-active': quickFilterActive,
			}"
			data-element="booking-grid-cell"
			:data-resource-id="cell.resourceId"
			:data-from="cell.fromTs"
			:data-to="cell.toTs"
			@mouseenter="mouseEnterHandler"
			@mouseleave="mouseLeaveHandler"
			@mousemove="mouseMoveHandler"
		></div>
	`,
};
