import { Dom, Event, Type } from 'main.core';
import { createNamespacedHelpers } from 'ui.vue3.vuex';
import { BusySlot as BusySlotType, Model } from 'booking.const';
import { grid } from 'booking.lib.grid';

import { BusyPopup } from './busy-popup/busy-popup';
import './busy-slot.css';

const { mapGetters: mapInterfaceGetters } = createNamespacedHelpers(Model.Interface);

const BookingBusySlotClassName = 'booking-booking-busy-slot';

type BusySlotData = {
	isPopupShown: boolean,
};

export const BusySlot = {
	name: 'BusySlot',
	props: {
		busySlot: {
			type: Object,
			required: true,
		},
	},
	setup(): { BookingBusySlotClassName: string }
	{
		return {
			BookingBusySlotClassName,
		};
	},
	data(): BusySlotData
	{
		return {
			isPopupShown: false,
		};
	},
	computed: {
		...mapInterfaceGetters({
			disabledBusySlots: 'disabledBusySlots',
			isFilterMode: 'isFilterMode',
			isEditingBookingMode: 'isEditingBookingMode',
			isDragMode: 'isDragMode',
		}),
		isDisabled(): boolean
		{
			const isDragOffHours = this.isDragMode && this.busySlot.type === BusySlotType.OffHours;
			if (this.isFilterMode || isDragOffHours)
			{
				return true;
			}

			return this.busySlot.id in this.disabledBusySlots;
		},
		left(): number
		{
			return grid.calculateLeft(this.busySlot.resourceId);
		},
		top(): number
		{
			return grid.calculateTop(this.busySlot.fromTs);
		},
		height(): number
		{
			return grid.calculateHeight(this.busySlot.fromTs, this.busySlot.toTs);
		},
	},
	methods: {
		onClick(): void
		{
			if (
				this.isFilterMode
				|| this.isEditingBookingMode
				|| this.busySlot.type === BusySlotType.Intersection
			)
			{
				return;
			}

			void this.$store.dispatch(`${Model.Interface}/addDisabledBusySlot`, this.busySlot);
		},
		onMouseEnter(): void
		{
			clearTimeout(this.showTimeout);
			this.showTimeout = setTimeout(() => this.showPopup(), 300);
			Event.unbind(document, 'mousemove', this.onMouseMove);
			Event.bind(document, 'mousemove', this.onMouseMove);
		},
		onMouseMove(event: MouseEvent): void
		{
			if (this.cursorInsideContainer(event.target))
			{
				this.updatePopup(event);
			}
			else
			{
				Event.unbind(document, 'mousemove', this.onMouseMove);
				this.closePopup();
			}
		},
		onMouseLeave(event: MouseEvent): void
		{
			if (event.relatedTarget?.closest('.popup-window')?.querySelector('.booking-booking-busy-popup'))
			{
				return;
			}

			Event.unbind(document, 'mousemove', this.onMouseMove);
			this.closePopup();
		},
		cursorInsideContainer(eventTarget: EventTarget | null): boolean
		{
			return !Type.isNull(eventTarget) && Dom.hasClass(eventTarget, this.BookingBusySlotClassName);
		},
		updatePopup(event: MouseEvent): void
		{
			const rect = this.$refs.container?.getBoundingClientRect();
			if (
				this.isDragMode
				|| !rect
				|| event.clientY > rect.top + rect.height
				|| event.clientY < rect.top
				|| event.clientX < rect.left
				|| event.clientX > rect.left + rect.width
			)
			{
				this.closePopup();

				return;
			}

			this.showTimeout ??= setTimeout(() => this.showPopup(), 300);
		},
		showPopup(): void
		{
			this.isPopupShown = true;
		},
		closePopup(): void
		{
			clearTimeout(this.showTimeout);
			this.showTimeout = null;
			this.isPopupShown = false;
		},
	},
	components: {
		BusyPopup,
	},
	template: `
		<div
			v-if="left >= 0"
			:class="[BookingBusySlotClassName, {
				'--disabled': isDisabled,
			}]"
			:style="{
				'--left': left + 'px',
				'--top': top + 'px',
				'--height': height + 'px',
			}"
			data-element="booking-busy-slot"
			:data-id="busySlot.resourceId"
			:data-from="busySlot.fromTs"
			:data-to="busySlot.toTs"
			ref="container"
			@click.stop="onClick"
			@mouseenter.stop="onMouseEnter"
			@mouseleave.stop="onMouseLeave"
		></div>
		<BusyPopup
			v-if="isPopupShown"
			:busySlot="busySlot"
			@close="closePopup"
		/>
	`,
};
