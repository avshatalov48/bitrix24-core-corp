import { Dom, Event } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { mapGetters } from 'ui.vue3.vuex';
import { Ears } from 'ui.ears';

import { AhaMoment, DraggedElementKind, Model } from 'booking.const';
import { ahaMoments } from 'booking.lib.aha-moments';
import { Drag } from 'booking.lib.drag';
import { grid } from 'booking.lib.grid';
import type { BookingModel } from 'booking.model.bookings';

import { LeftPanel } from './left-panel/left-panel';
import { NowLine } from './now-line/now-line';
import { Bookings } from './bookings/bookings';
import { Column } from './column/column';
import { ScalePanel } from './scale-panel/scale-panel';
import { Sidebar } from './sidebar/sidebar';
import { DragDelete } from './drag-delete/drag-delete';
import './grid.css';

type GridData = {
	scrolledToBooking: boolean,
};

// @vue/component
export const Grid = {
	name: 'BookingsGrid',
	components: {
		LeftPanel,
		NowLine,
		Column,
		Bookings,
		ScalePanel,
		Sidebar,
		DragDelete,
	},
	data(): GridData
	{
		return {
			scrolledToBooking: false,
		};
	},
	computed: {
		...mapGetters({
			resourcesIds: `${Model.Interface}/resourcesIds`,
			scroll: `${Model.Interface}/scroll`,
			editingBookingId: `${Model.Interface}/editingBookingId`,
			editingWaitListItemId: `${Model.Interface}/editingWaitListItemId`,
			isFeatureEnabled: `${Model.Interface}/isFeatureEnabled`,
			isLoaded: `${Model.Interface}/isLoaded`,
			selectedDateTs: `${Model.Interface}/selectedDateTs`,
			filteredBookingsIds: `${Model.Filter}/filteredBookingsIds`,
			isFilterMode: `${Model.Filter}/isFilterMode`,
		}),
		editingBooking(): BookingModel | null
		{
			return this.$store.getters['bookings/getById'](this.editingBookingId) ?? null;
		},
	},
	watch: {
		scroll(value): void
		{
			this.$refs.columnsContainer.scrollLeft = value;
		},
		editingBooking(): void
		{
			this.scrollToEditingBooking();
		},
		isLoaded(isLoaded): void
		{
			if (isLoaded)
			{
				let dataId = null;
				let dataKind = null;
				if (this.editingBookingId)
				{
					dataId = this.editingBookingId;
					dataKind = DraggedElementKind.Booking;
				}

				if (this.editingWaitListItemId)
				{
					dataId = this.editingWaitListItemId;
					dataKind = DraggedElementKind.WaitListItem;
				}

				this.initDragManager(dataId, dataKind);
			}
		},
		editingBookingId(id: number | string): void
		{
			if (id)
			{
				this.initDragManager(id, DraggedElementKind.Booking);
			}
		},
		editingWaitListItemId(id: number | string): void
		{
			if (id)
			{
				this.initDragManager(id, DraggedElementKind.WaitListItem);
			}
		},
		filteredBookingsIds(ids: number[]): void
		{
			if (!this.isFilterMode || ids.length === 0)
			{
				return;
			}

			const booking: BookingModel | null = this.$store.getters['bookings/getById'](ids[0]) ?? null;
			if (booking !== null)
			{
				this.scrollToBooking(booking);
			}
		},
	},
	mounted(): void
	{
		this.ears = new Ears({
			container: this.$refs.columnsContainer,
			smallSize: true,
			className: 'booking-booking-grid-columns-ears',
		}).init();

		EventEmitter.subscribe('BX.Main.Popup:onAfterClose', this.tryShowAhaMoment);
		EventEmitter.subscribe('BX.Main.Popup:onDestroy', this.tryShowAhaMoment);
	},
	beforeUnmount(): void
	{
		this.dragManager?.destroy();
	},
	unmounted()
	{
		EventEmitter.unsubscribe('BX.Main.Popup:onAfterClose', this.tryShowAhaMoment);
		EventEmitter.unsubscribe('BX.Main.Popup:onDestroy', this.tryShowAhaMoment);
	},
	methods: {
		updateEars(): void
		{
			this.ears.toggleEars();

			this.tryShowAhaMoment();
		},
		areEarsShown(): boolean
		{
			const shownClass = 'ui-ear-show';

			return (
				Dom.hasClass(this.ears.getRightEar(), shownClass)
				|| Dom.hasClass(this.ears.getLeftEar(), shownClass)
			);
		},
		scrollToEditingBooking(): void
		{
			if (!this.editingBooking || this.scrolledToBooking)
			{
				return;
			}

			this.scrollToBooking(this.editingBooking);
		},
		scrollToBooking(booking: BookingModel): void
		{
			const top = grid.calculateTop(booking.dateFromTs);
			const height = grid.calculateHeight(booking.dateFromTs, booking.dateToTs);
			this.$refs.inner.scrollTop = top + height / 2 + this.$refs.inner.offsetHeight / 2;
			this.scrolledToBooking = true;
		},
		tryShowAhaMoment(): void
		{
			if (this.areEarsShown() && ahaMoments.shouldShow(AhaMoment.ExpandGrid))
			{
				Event.EventEmitter.unsubscribe('BX.Main.Popup:onAfterClose', this.tryShowAhaMoment);
				Event.EventEmitter.unsubscribe('BX.Main.Popup:onDestroy', this.tryShowAhaMoment);
				void this.$refs.scalePanel.showAhaMoment();
			}
		},
		initDragManager(id: number | string = '', kind: $Values<typeof DraggedElementKind> = null): void
		{
			if (this.isFeatureEnabled)
			{
				const dataId = id ? `[data-id="${id}"]` : '';
				const dataKind = kind ? `[data-kind="${kind}"]` : '';

				this.dragManager = new Drag({
					container: this.$el.parentElement,
					draggable: `.booking--draggable-item${dataId}${dataKind}`,
				});
			}
		},
	},
	template: `
		<div ref="bookingContainer" class="booking-booking-grid">
			<div
				id="booking-booking-grid-wrap"
				class="booking-booking-grid-inner --vertical-scroll-bar"
				ref="inner"
			>
				<LeftPanel/>
				<NowLine/>
				<div
					id="booking-booking-grid-columns"
					class="booking-booking-grid-columns --horizontal-scroll-bar"
					ref="columnsContainer"
					@scroll="$store.dispatch('interface/setScroll', $refs.columnsContainer.scrollLeft)"
				>
					<Bookings/>
					<TransitionGroup
						name="booking-transition-resource"
						@after-leave="updateEars"
						@after-enter="updateEars"
					>
						<template v-for="resourceId of resourcesIds" :key="resourceId">
							<Column :resourceId="resourceId"/>
						</template>
					</TransitionGroup>
				</div>
			</div>
			<ScalePanel
				:getColumnsContainer="() => $refs.columnsContainer"
				ref="scalePanel"
			/>
			<DragDelete/>
		</div>
		<Sidebar/>
	`,
};
