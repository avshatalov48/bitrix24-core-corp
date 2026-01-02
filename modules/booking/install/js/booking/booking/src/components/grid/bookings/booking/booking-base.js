import { mapGetters } from 'ui.vue3.vuex';

import { DraggedElementKind, Model, VisitStatus } from 'booking.const';
import { Communication, DisabledPopup } from 'booking.component.booking';
import { Duration } from 'booking.lib.duration';
import { mousePosition } from 'booking.lib.mouse-position';
import { isRealId } from 'booking.lib.is-real-id';
import { grid } from 'booking.lib.grid';
import type { BookingModel } from 'booking.model.bookings';
import type { ClientModel, ClientData } from 'booking.model.clients';

import { BookingAddClient } from './add-client/add-client';
import { BookingTime } from './booking-time/booking-time';
import { Actions } from './actions/actions';
import { BookingName } from './name/name';
import { BookingNote } from './note/note';
import { BookingProfit } from './profit/profit';
import { BookingCrmButton } from './crm-button/crm-button';
import { Counter } from './counter/counter';
import { Resize } from './resize/resize';
import { BookingWidth } from './const';
import type { BookingUiDuration } from './types';
import './booking.css';

export type { BookingUiDuration };

// @vue/component
export const BookingBase = {
	name: 'BookingBase',
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
		width: {
			type: Number,
			default: BookingWidth,
		},
		leftOffset: {
			type: Number,
			default: 0,
		},
		bookingClass: {
			type: [String, Object, Array],
			default: '',
		},
		bookingStyle: {
			type: [String, Object, Array],
			default: '',
		},
	},
	data(): Object
	{
		return {
			visible: true,
			isDisabledPopupShown: false,
			resizeFromTs: null,
			resizeToTs: null,
			visibleNotePopup: false,
		};
	},
	mounted(): void
	{
		this.updateVisibility();
		this.updateVisibilityDuringTransition();

		setTimeout(() => {
			if (!this.isReal && mousePosition.isMousePressed())
			{
				void this.$refs.resize.startResize();
			}
		}, 300);
	},
	beforeUnmount(): void
	{
		if (this.deletingBookings[this.bookingId] || !this.booking?.resourcesIds.includes(this.resourceId))
		{
			this.$el.remove();
		}
	},
	computed: {
		...mapGetters({
			resourcesIds: `${Model.Interface}/resourcesIds`,
			zoom: `${Model.Interface}/zoom`,
			scroll: `${Model.Interface}/scroll`,
			draggedBookingId: `${Model.Interface}/draggedBookingId`,
			draggedDataTransfer: `${Model.Interface}/draggedDataTransfer`,
			editingBookingId: `${Model.Interface}/editingBookingId`,
			isEditingBookingMode: `${Model.Interface}/isEditingBookingMode`,
			deletingBookings: `${Model.Interface}/deletingBookings`,
		}),
		isReal(): boolean
		{
			return isRealId(this.bookingId);
		},
		booking(): BookingModel
		{
			return this.$store.getters[`${Model.Bookings}/getById`](this.bookingId);
		},
		client(): ClientModel
		{
			const clientData: ClientData = this.booking.primaryClient;

			return clientData ? this.$store.getters[`${Model.Clients}/getByClientData`](clientData) : null;
		},
		left(): number
		{
			return grid.calculateLeft(this.resourceId);
		},
		top(): number
		{
			return grid.calculateTop(this.dateFromTs);
		},
		height(): number
		{
			return grid.calculateHeight(this.dateFromTs, this.dateToTs);
		},
		realHeight(): number
		{
			return grid.calculateRealHeight(this.dateFromTs, this.dateToTs);
		},
		dateFromTs(): number
		{
			return this.resizeFromTs ?? this.booking.dateFromTs;
		},
		dateToTs(): number
		{
			return this.resizeToTs ?? this.booking.dateToTs;
		},
		dateFromTsRounded(): number
		{
			return this.roundTimestamp(this.resizeFromTs) ?? this.dateFromTs;
		},
		dateToTsRounded(): number
		{
			return this.roundTimestamp(this.resizeToTs) ?? this.dateToTs;
		},
		disabled(): boolean
		{
			return this.isEditingBookingMode && this.editingBookingId !== this.bookingId;
		},
		bookingOffset(): number
		{
			return this.leftOffset * this.zoom;
		},
		isExpiredBooking(): boolean
		{
			return this.booking.dateToTs < this.nowTs;
		},
		isNotVisited(): boolean
		{
			const started = this.nowTs > this.booking.dateFromTs;
			const statusUnknown = this.booking.visitStatus === VisitStatus.Unknown;
			const statusNotVisited = this.booking.visitStatus === VisitStatus.NotVisited;

			return (started && statusUnknown) || statusNotVisited;
		},
		disabledHover(): boolean
		{
			return (
				this.draggedDataTransfer.id > 0
				&& (
					this.draggedDataTransfer.kind !== DraggedElementKind.Booking
					|| this.draggedDataTransfer.id !== this.bookingId
				)
			);
		},
	},
	methods: {
		updateVisibilityDuringTransition(): void
		{
			this.animation?.stop();
			// eslint-disable-next-line new-cap
			this.animation = new BX.easing({
				duration: 200,
				start: {},
				finish: {},
				step: this.updateVisibility,
			});
			this.animation.animate();
		},
		updateVisibility(): void
		{
			if (!this.$el)
			{
				return;
			}

			const rect = this.$el.getBoundingClientRect();
			this.visible = rect.right > 0 && rect.left < window.innerWidth;
		},
		onNoteMouseEnter(): void
		{
			this.showNoteTimeout = setTimeout((): void => {
				this.visibleNotePopup = true;
			}, 100);
		},
		onNoteMouseLeave(): void
		{
			clearTimeout(this.showNoteTimeout);
			this.visibleNotePopup = false;
		},
		onClick(event: PointerEvent): void
		{
			if (this.disabled)
			{
				this.isDisabledPopupShown = true;

				event.stopPropagation();
			}
		},
		resizeUpdate(resizeFromTs: number | null, resizeToTs: number | null): void
		{
			this.resizeFromTs = resizeFromTs;
			this.resizeToTs = resizeToTs;
		},
		roundTimestamp(timestamp: number | null): number | null
		{
			const fiveMinutes = Duration.getUnitDurations().i * 5;

			return timestamp ? Math.round(timestamp / fiveMinutes) * fiveMinutes : null;
		},
	},
	watch: {
		scroll(): void
		{
			this.updateVisibility();
		},
		zoom(): void
		{
			this.updateVisibility();
		},
		resourcesIds(): void
		{
			this.updateVisibilityDuringTransition();
		},
		visible(visible): void
		{
			if (visible)
			{
				return;
			}

			setTimeout(() => {
				this.updateVisibility();
			}, 2000);
		},
	},
	components: {
		Actions,
		BookingAddClient,
		BookingCrmButton,
		BookingTime,
		BookingName,
		BookingNote,
		BookingProfit,
		Communication,
		Counter,
		DisabledPopup,
		Resize,
	},
	template: `
		<div
			class="booking-booking-booking booking--draggable-item"
			data-element="booking-booking"
			:data-id="bookingId"
			:data-resource-id="resourceId"
			data-kind="booking"
			:style="[bookingStyle, {
				'--left': left + bookingOffset + 'px',
				'--top': top + 'px',
				'--height': height + 'px',
				'--width': width + 'px',
			}].flat(1)"
			:class="[bookingClass, {
				'--not-real': !isReal,
				'--zoom-is-less-than-08': zoom < 0.8,
				'--compact-mode': realHeight < 40 || zoom < 0.8,
				'--small': realHeight <= 15,
				'--long': realHeight >= 65,
				'--disabled': disabled,
				'--confirmed': booking.isConfirmed && !isNotVisited,
				'--expired': isExpiredBooking,
				'--not-visited': isNotVisited,
				'--resizing': resizeFromTs && resizeToTs,
				'--no-pointer-events': disabledHover,
			}].flat(1)"
			@click.capture="onClick"
		>
			<div v-if="visible" class="booking-booking-booking-padding">
				<Counter :bookingId="bookingId" :nowTs="nowTs"/>
				<div class="booking-booking-booking-inner">
					<div class="booking-booking-booking-content">
						<div class="booking-booking-booking-content-row">
							<div
								class="booking-booking-booking-name-container"
								@mouseenter="onNoteMouseEnter"
								@mouseleave="onNoteMouseLeave"
								@click="visibleNotePopup = true"
							>
								<BookingName :bookingId="bookingId" :resourceId="resourceId"/>
								<BookingNote
									:bookingId="bookingId"
									:bindElement="() => $el"
									:visiblePopup="visibleNotePopup"
									ref="note"
								/>
							</div>
							<BookingTime
								:bookingId="bookingId"
								:resourceId="resourceId"
								:dateFromTs="dateFromTsRounded"
								:dateToTs="dateToTsRounded"
							/>
							<BookingProfit :bookingId="bookingId" :resourceId="resourceId"/>
						</div>
						<div class="booking-booking-booking-content-row --lower">
							<BookingTime
								:bookingId="bookingId"
								:resourceId="resourceId"
								:dateFromTs="dateFromTsRounded"
								:dateToTs="dateToTsRounded"
							/>
							<div v-if="client" class="booking-booking-booking-buttons">
								<Communication/>
								<BookingCrmButton :bookingId="bookingId"/>
							</div>
							<BookingAddClient
								v-else
								:bookingId="bookingId"
								:resourceId="resourceId"
								:expired="isExpiredBooking"
							/>
						</div>
					</div>
					<slot name="actions">
						<Actions :bookingId="bookingId" :resourceId="resourceId"/>
					</slot>
				</div>
			</div>
			<Resize
				v-if="!disabled"
				:bookingId="bookingId"
				:resourceId="resourceId"
				ref="resize"
				@update="resizeUpdate"
			/>
			<DisabledPopup
				v-if="isDisabledPopupShown"
				:popupId="['booking-booking-disabled-popup', bookingId, resourceId].join('-')"
				:bindElement="() => $el"
				contentClass="booking-booking-disabled-popup-content"
				@close="isDisabledPopupShown = false"
			/>
			<slot/>
		</div>
	`,
};
