import { DateTimeFormat } from 'main.date';
import { mapGetters } from 'ui.vue3.vuex';

import { Model } from 'booking.const';
import type { BookingModel } from 'booking.model.bookings';

import type { BusySlotDto } from '../../../../lib/busy-slots/busy-slots';
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
			hovered: false,
			halfOffset: 0,
		};
	},
	computed: {
		...mapGetters({
			zoom: `${Model.Interface}/zoom`,
			disabledBusySlots: `${Model.Interface}/disabledBusySlots`,
			selectedCells: `${Model.Interface}/selectedCells`,
			bookings: `${Model.Bookings}/get`,
			isFilterMode: `${Model.Interface}/isFilterMode`,
			isEditingBookingMode: `${Model.Interface}/isEditingBookingMode`,
			busySlots: `${Model.Interface}/busySlots`,
		}),
		activeBusySlots(): BusySlotDto[]
		{
			return this.busySlots.filter(({ id }) => !(id in this.disabledBusySlots));
		},
		isAvailable(): boolean
		{
			if (this.isFilterMode || this.isEditingBookingMode)
			{
				return false;
			}

			const { fromTs, toTs } = this.freeSpace;

			const cellFromTs = this.cell.fromTs;
			const cellHalfTs = this.cell.fromTs + halfHour;

			return (toTs > cellFromTs || toTs > cellHalfTs) && (toTs - fromTs) >= this.duration;
		},
		timeFormatted(): string
		{
			const timeFormat = DateTimeFormat.getFormat('SHORT_TIME_FORMAT');

			return this.loc('BOOKING_BOOKING_TIME_RANGE', {
				'#FROM#': DateTimeFormat.format(timeFormat, this.fromTs / 1000),
				'#TO#': DateTimeFormat.format(timeFormat, this.toTs / 1000),
			});
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
			return this.cell.toTs - this.cell.fromTs;
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
			return [
				...this.bookings
					.filter((booking: BookingModel) => booking.resourcesIds.includes(this.cell.resourceId))
					.map(({ dateFromTs, dateToTs }) => ({ fromTs: dateFromTs, toTs: dateToTs })),
				...this.activeBusySlots
					.filter((busySlot: BusySlotDto) => busySlot.resourceId === this.cell.resourceId)
					.map(({ fromTs, toTs }) => ({ fromTs, toTs })),
				...Object.values(this.selectedCells)
					.filter((cell: CellDto) => cell.resourceId === this.cell.resourceId)
					.map(({ fromTs, toTs }) => ({ fromTs, toTs })),
			];
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

			const clientY = event.clientY - window.scrollY;
			const rect = this.$el.getBoundingClientRect();
			const bottomHalf = clientY > (rect.top + rect.top + rect.height) / 2;
			const canSubtractHalfHour = this.fromTs - this.halfOffset >= this.freeSpace.fromTs;
			const canAddHalfHour = this.toTs - this.halfOffset + halfHour <= this.freeSpace.toTs;

			if ((bottomHalf && canAddHalfHour) || (!bottomHalf && !canSubtractHalfHour))
			{
				this.halfOffset = halfHour;
			}

			if ((!bottomHalf && canSubtractHalfHour) || (bottomHalf && !canAddHalfHour))
			{
				this.halfOffset = 0;
			}

			const offsetNotMatchesHalf = (bottomHalf && this.halfOffset === 0) || (!bottomHalf && this.halfOffset !== 0);

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
	template: `
		<div
			class="booking-booking-grid-cell"
			data-element="booking-grid-cell"
			:data-resource-id="cell.resourceId"
			:data-from="cell.fromTs"
			:data-to="cell.toTs"
			@mouseenter="mouseEnterHandler"
			@mouseleave="mouseLeaveHandler"
			@mousemove="mouseMoveHandler"
		>
		</div>
	`,
};
