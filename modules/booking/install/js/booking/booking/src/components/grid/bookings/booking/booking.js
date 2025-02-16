import { mapGetters } from 'ui.vue3.vuex';

import { Counter as UiCounter, CounterSize, CounterColor } from 'booking.component.counter';
import { CrmEntity, Model } from 'booking.const';
import type { BookingModel, DealData } from 'booking.model.bookings';
import type { ClientModel, ClientData } from 'booking.model.clients';

import { grid } from '../../../../lib/grid/grid';

import { AddClient } from './add-client/add-client';
import { BookingTime } from './booking-time/booking-time';
import { Actions } from './actions/actions';
import { Note } from './note/note';
import { Communication } from './communication/communication';
import { DisabledPopup } from './disabled-popup/disabled-popup';
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
		};
	},
	mounted(): void
	{
		this.updateVisibility();
		this.updateVisibilityDuringTransition();
	},
	computed: {
		...mapGetters({
			resourcesIds: `${Model.Interface}/resourcesIds`,
			zoom: `${Model.Interface}/zoom`,
			scroll: `${Model.Interface}/scroll`,
			editingBookingId: `${Model.Interface}/editingBookingId`,
			isEditingBookingMode: `${Model.Interface}/isEditingBookingMode`,
		}),
		booking(): BookingModel
		{
			return this.$store.getters[`${Model.Bookings}/getById`](this.bookingId);
		},
		client(): ClientModel
		{
			const clientData: ClientData = this.booking.primaryClient;

			return clientData ? this.$store.getters[`${Model.Clients}/getByClientData`](clientData) : null;
		},
		deal(): DealData | null
		{
			return this.booking.externalData?.find((data) => data.entityTypeId === CrmEntity.Deal) ?? null;
		},
		bookingName(): string
		{
			return this.client ? this.client.name : this.booking.name;
		},
		left(): number
		{
			return grid.calculateLeft(this.resourceId);
		},
		top(): number
		{
			return grid.calculateTop(this.booking.dateFromTs);
		},
		height(): number
		{
			return grid.calculateHeight(this.booking.dateFromTs, this.booking.dateToTs);
		},
		realHeight(): number
		{
			return grid.calculateRealHeight(this.booking.dateFromTs, this.booking.dateToTs);
		},
		disabled(): boolean
		{
			return this.isEditingBookingMode && this.editingBookingId !== this.bookingId;
		},
		counterOptions(): Object
		{
			return Object.freeze({
				color: CounterColor.DANGER,
				size: CounterSize.LARGE,
			});
		},
		overlappingBookings(): BookingUiDuration[]
		{
			const { dateFromTs } = this.booking;
			const uiBooking = this.uiBookings.find((booking) => dateFromTs === booking.fromTs);

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
			if (!this.$refs.container)
			{
				return;
			}

			const rect = this.$refs.container.getBoundingClientRect();
			this.visible = rect.right > 0 && rect.left < window.innerWidth;
		},
		getBindElement(): HTMLElement
		{
			return this.$refs.container;
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
		Note,
		Communication,
		UiCounter,
		DisabledPopup,
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
				'--zoom-is-less-than-08': zoom < 0.8,
				'--compact-mode': realHeight < 40 || zoom < 0.8,
				'--small': realHeight <= 12.5,
				'--short': shortView,
				'--disabled': disabled,
				'--confirmed': booking.isConfirmed,
				'--expired': isExpiredBooking,
			}"
			ref="container"
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
								<div
									class="booking-booking-booking-name"
									:title="bookingName"
									data-element="booking-booking-name"
									:data-id="bookingId"
									:data-resource-id="resourceId"
								>
									{{ bookingName }}
								</div>
								<Note
									:bookingId="bookingId"
									:bindElement="getBindElement"
									ref="note"
								/>
							</div>
							<BookingTime
								:bookingId="bookingId"
								:resourceId="resourceId"
							/>
							<div
								v-if="deal"
								class="booking-booking-booking-profit"
								data-element="booking-booking-profit"
								:data-id="bookingId"
								:data-resource-id="resourceId"
								:data-profit="deal.data.opportunity"
								v-html="deal.data.formattedOpportunity"
							></div>
						</div>
						<div class="booking-booking-booking-content-row --lower">
							<BookingTime
								:bookingId="bookingId"
								:resourceId="resourceId"
							/>
							<Communication v-if="client"/>
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
			<UiCounter
				v-if="booking.counter > 0"
				:value="booking.counter"
				:color="counterOptions.color"
				:size="counterOptions.size"
				border
				counter-class="booking--counter"
			/>
			<DisabledPopup
				v-if="isDisabledPopupShown"
				:bookingId="bookingId"
				:resourceId="resourceId"
				:bindElement="() => $refs.container"
				@close="isDisabledPopupShown = false"
			/>
		</div>
	`,
};
