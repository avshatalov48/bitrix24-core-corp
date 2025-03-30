import { mapGetters } from 'ui.vue3.vuex';

import { Model } from 'booking.const';
import { Duration } from 'booking.lib.duration';
import { mousePosition } from 'booking.lib.mouse-position';
import { isRealId } from 'booking.lib.is-real-id';
import { grid } from 'booking.lib.grid';
import type { BookingModel } from 'booking.model.bookings';
import type { ClientModel, ClientData } from 'booking.model.clients';


import { AddClient } from './add-client/add-client';
import { BookingTime } from './booking-time/booking-time';
import { Actions } from './actions/actions';
import { Name } from './name/name';
import { Note } from './note/note';
import { Profit } from './profit/profit';
import { Communication } from './communication/communication';
import { CrmButton } from './crm-button/crm-button';
import { Counter } from './counter/counter';
import { DisabledPopup } from './disabled-popup/disabled-popup';
import { Resize } from './resize/resize';
import type { BookingUiDuration } from './types';
import './booking.css';

export type { BookingUiDuration };

const BookingWidth = 280;

export const Booking = {
	name: 'Booking',
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
		 * @param {BookingUiDuration[]} uiBookings
		 */
		uiBookings: {
			type: Array,
			default: () => [],
		},
	},
	data(): Object
	{
		return {
			visible: true,
			isDisabledPopupShown: false,
			resizeFromTs: null,
			resizeToTs: null,
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
		}, 200);
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
		overlappingBookings(): BookingUiDuration[]
		{
			const uiBooking = this.uiBookings.find((booking) => this.booking.dateFromTs === booking.fromTs);
			if (!uiBooking)
			{
				return [];
			}

			const { fromTs, toTs } = uiBooking;

			return [
				...this.uiBookings.filter((booking) => fromTs > booking.fromTs && fromTs < booking.toTs),
				uiBooking,
				...this.uiBookings.filter((booking) => toTs > booking.fromTs && toTs < booking.toTs),
			];
		},
		bookingWidth(): number
		{
			const overlappingBookingsCount = this.overlappingBookings.length;

			return BookingWidth / (overlappingBookingsCount > 0 ? overlappingBookingsCount : 1);
		},
		bookingOffset(): number
		{
			const index = this.overlappingBookings.findIndex(({ id }) => id === this.booking.id);

			return this.bookingWidth * this.zoom * index;
		},
		shortView(): boolean
		{
			return this.overlappingBookings.length > 1;
		},
		isExpiredBooking(): boolean
		{
			return this.booking.dateToTs < this.nowTs;
		},
	},
	methods: {
		updateVisibilityDuringTransition(): void
		{
			this.animation?.stop();
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
			this.showNoteTimeout = setTimeout(() => this.$refs.note.showViewPopup(), 100);
		},
		onNoteMouseLeave(): void
		{
			clearTimeout(this.showNoteTimeout);
			this.$refs.note.closeViewPopup();
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
	},
	components: {
		AddClient,
		BookingTime,
		Actions,
		Name,
		Note,
		Profit,
		Communication,
		CrmButton,
		Counter,
		DisabledPopup,
		Resize,
	},
	template: `
		<div
			class="booking-booking-booking"
			data-element="booking-booking"
			:data-id="bookingId"
			:data-resource-id="resourceId"
			:style="{
				'--left': left + bookingOffset + 'px',
				'--top': top + 'px',
				'--height': height + 'px',
				'--width': bookingWidth + 'px',
			}"
			:class="{
				'--not-real': !isReal,
				'--zoom-is-less-than-08': zoom < 0.8,
				'--compact-mode': realHeight < 40 || zoom < 0.8,
				'--small': realHeight <= 15,
				'--short': shortView,
				'--disabled': disabled,
				'--confirmed': booking.isConfirmed,
				'--expired': isExpiredBooking,
				'--resizing': resizeFromTs && resizeToTs,
			}"
			@click.capture="onClick"
		>
			<div v-if="visible" class="booking-booking-booking-padding">
				<div class="booking-booking-booking-inner">
					<div class="booking-booking-booking-content">
						<div class="booking-booking-booking-content-row">
							<div
								v-show="!shortView"
								class="booking-booking-booking-name-container"
								@mouseenter="onNoteMouseEnter"
								@mouseleave="onNoteMouseLeave"
								@click="$refs.note.showViewPopup()"
							>
								<Name :bookingId="bookingId" :resourceId="resourceId"/>
								<Note
									:bookingId="bookingId"
									:bindElement="() => $el"
									ref="note"
								/>
							</div>
							<BookingTime
								:bookingId="bookingId"
								:resourceId="resourceId"
								:dateFromTs="dateFromTsRounded"
								:dateToTs="dateToTsRounded"
							/>
							<Profit :bookingId="bookingId" :resourceId="resourceId"/>
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
								<CrmButton :bookingId="bookingId"/>
							</div>
							<AddClient
								v-else
								:bookingId="bookingId"
								:resourceId="resourceId"
								:expired="isExpiredBooking"
							/>
						</div>
					</div>
					<Actions :bookingId="bookingId" :resourceId="resourceId"/>
				</div>
			</div>
			<Resize
				v-if="!disabled"
				:bookingId="bookingId"
				:resourceId="resourceId"
				ref="resize"
				@update="resizeUpdate"
			/>
			<Counter :bookingId="bookingId"/>
			<DisabledPopup
				v-if="isDisabledPopupShown"
				:bookingId="bookingId"
				:resourceId="resourceId"
				:bindElement="() => $el"
				@close="isDisabledPopupShown = false"
			/>
		</div>
	`,
};
