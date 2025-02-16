import { Event } from 'main.core';
import { PopupOptions } from 'main.popup';
import { mapGetters } from 'ui.vue3.vuex';

import { Model } from 'booking.const';
import { Popup } from 'booking.component.popup';
import { TimeSelector } from 'booking.component.time-selector';
import { Button, ButtonSize, ButtonColor, ButtonIcon } from 'booking.component.button';
import { bookingService } from 'booking.provider.service.booking-service';
import type { BookingModel } from 'booking.model.bookings';
import './change-time-popup.css';

export const ChangeTimePopup = {
	emits: ['close'],
	props: {
		bookingId: {
			type: [Number, String],
			required: true,
		},
		targetNode: HTMLElement,
	},
	data(): Object
	{
		return {
			ButtonSize,
			ButtonColor,
			ButtonIcon,
			fromTs: 0,
			toTs: 0,
			duration: 0,
		};
	},
	created(): void
	{
		this.fromTs = this.booking.dateFromTs;
		this.toTs = this.booking.dateToTs;
		this.duration = this.toTs - this.fromTs;
	},
	mounted(): void
	{
		Event.bind(document, 'scroll', this.adjustPosition, true);
	},
	beforeUnmount(): void
	{
		Event.unbind(document, 'scroll', this.adjustPosition, true);
	},
	computed: {
		...mapGetters({
			selectedDateTs: `${Model.Interface}/selectedDateTs`,
		}),
		popupId(): string
		{
			return `booking-change-time-popup-${this.bookingId}`;
		},
		config(): PopupOptions
		{
			return {
				className: 'booking-booking-change-time-popup',
				bindElement: this.targetNode,
				offsetTop: -10,
				bindOptions: {
					forceBindPosition: true,
					position: 'top',
				},
				angle: {
					offset: this.targetNode.offsetWidth / 2,
				},
			};
		},
		isBusy(): boolean
		{
			return this.bookings
				.filter(({ id }) => id !== this.bookingId)
				.some(({ dateToTs, dateFromTs }) => dateToTs > this.fromTs && this.toTs > dateFromTs)
			;
		},
		bookings(): BookingModel[]
		{
			return this.$store.getters[`${Model.Bookings}/getByDateAndResources`](
				this.selectedDateTs,
				this.booking.resourcesIds,
			);
		},
		booking(): BookingModel
		{
			return this.$store.getters['bookings/getById'](this.bookingId);
		},
	},
	methods: {
		adjustPosition(): void
		{
			this.$refs.popup.adjustPosition();
			this.$refs.timeFrom.adjustMenuPosition();
			this.$refs.timeTo.adjustMenuPosition();
		},
		closePopup(): void
		{
			const tsChanged = this.booking.dateFromTs !== this.fromTs || this.booking.dateToTs !== this.toTs;
			if (!this.isBusy && tsChanged)
			{
				void bookingService.update({
					id: this.booking.id,
					dateFromTs: this.fromTs,
					dateToTs: this.toTs,
					timezoneFrom: this.booking.timezoneFrom,
					timezoneTo: this.booking.timezoneTo,
				});
			}

			this.$emit('close');
		},
		freeze(): void
		{
			this.$refs.popup.getPopupInstance().setAutoHide(false);
		},
		unfreeze(): void
		{
			this.$refs.popup.getPopupInstance().setAutoHide(true);
		},
	},
	watch: {
		fromTs(): void
		{
			this.toTs = this.fromTs + this.duration;
		},
		toTs(): void
		{
			if (this.toTs <= this.fromTs)
			{
				this.fromTs = this.toTs - this.duration;
			}

			this.duration = this.toTs - this.fromTs;
		},
		isBusy(): void
		{
			setTimeout(() => this.adjustPosition(), 0);
		},
	},
	components: {
		Popup,
		TimeSelector,
		Button,
	},
	template: `
		<Popup
			:id="popupId"
			:config="config"
			@close="closePopup"
			ref="popup"
		>
			<div class="booking-booking-change-time-popup-content">
				<div class="booking-booking-change-time-popup-main">
					<TimeSelector
						v-model="fromTs"
						:hasError="isBusy"
						data-element="booking-change-time-from"
						:data-ts="fromTs"
						:data-booking-id="bookingId"
						ref="timeFrom"
						@freeze="freeze"
						@unfreeze="unfreeze"
						@enterSave="closePopup"
					/>
					<div class="booking-booking-change-time-popup-separator"></div>
					<TimeSelector
						v-model="toTs"
						:hasError="isBusy"
						:minTs="fromTs"
						data-element="booking-change-time-to"
						:data-ts="toTs"
						:data-booking-id="bookingId"
						ref="timeTo"
						@freeze="freeze"
						@unfreeze="unfreeze"
						@enterSave="closePopup"
					/>
					<Button
						class="booking-booking-change-time-popup-button"
						:size="ButtonIcon.MEDIUM"
						:color="ButtonColor.PRIMARY"
						:icon="ButtonIcon.DONE"
						:disabled="isBusy"
						@click="closePopup"
					/>
				</div>
				<div v-if="isBusy" class="booking-booking-change-time-popup-error">
					{{ loc('BOOKING_BOOKING_TIME_IS_NOT_AVAILABLE') }}
				</div>
			</div>
		</Popup>
	`,
};
