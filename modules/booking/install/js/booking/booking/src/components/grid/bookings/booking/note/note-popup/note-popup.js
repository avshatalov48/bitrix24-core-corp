import { Event } from 'main.core';
import { PopupOptions } from 'main.popup';
import { Resolvable } from 'booking.lib.resolvable';
import { Popup } from 'booking.component.popup';
import { bookingService } from 'booking.provider.service.booking-service';
import { Button, ButtonSize, ButtonColor } from 'booking.component.button';
import type { BookingModel } from 'booking.model.bookings';
import './note-popup.css';

export const NotePopup = {
	emits: ['close'],
	props: {
		bookingId: {
			type: [Number, String],
			required: true,
		},
		bindElement: {
			type: Function,
			required: true,
		},
		isEditMode: {
			type: Boolean,
			required: true,
		},
	},
	data(): Object
	{
		return {
			ButtonSize,
			ButtonColor,
			note: '',
			mountedPromise: new Resolvable(),
		};
	},
	created(): void
	{
		this.note = this.bookingNote;
	},
	mounted(): void
	{
		this.mountedPromise.resolve();
		this.adjustPosition();
		this.focusOnTextarea();
		Event.bind(document, 'scroll', this.adjustPosition, true);
	},
	beforeUnmount(): void
	{
		Event.unbind(document, 'scroll', this.adjustPosition, true);
	},
	computed: {
		bookingNote(): string
		{
			return this.booking.note ?? '';
		},
		booking(): BookingModel
		{
			return this.$store.getters['bookings/getById'](this.bookingId);
		},
		popupId(): string
		{
			return `booking-booking-note-popup-${this.bookingId}`;
		},
		config(): PopupOptions
		{
			return {
				className: 'booking-booking-note-popup',
				bindElement: this.bindElement(),
				minWidth: this.bindElement().offsetWidth,
				height: 120,
				offsetTop: -10,
				background: 'var(--ui-color-background-note)',
				bindOptions: {
					forceBindPosition: true,
					position: 'top',
				},
				autoHide: this.isEditMode,
			};
		},
	},
	methods: {
		saveNote(): void
		{
			const note = this.note.trim();
			if (this.bookingNote !== note)
			{
				void bookingService.update({
					id: this.booking.id,
					note,
				});
			}

			this.closePopup();
		},
		onMouseDown(): void
		{
			Event.unbind(window, 'mouseup', this.onMouseUp);
			Event.bind(window, 'mouseup', this.onMouseUp);
			this.setAutoHide(false);
		},
		onMouseUp(): void
		{
			Event.unbind(window, 'mouseup', this.onMouseUp);
			setTimeout(() => this.setAutoHide(this.isEditMode), 0);
		},
		setAutoHide(autoHide: boolean): void
		{
			this.$refs.popup?.getPopupInstance()?.setAutoHide(autoHide);
		},
		adjustPosition(): void
		{
			this.$refs.popup.adjustPosition();
		},
		closePopup(): void
		{
			this.$emit('close');
		},
		focusOnTextarea(): void
		{
			setTimeout(() => {
				if (this.isEditMode)
				{
					this.$refs.textarea.focus();
				}
			}, 0);
		},
	},
	watch: {
		isEditMode(isEditMode: boolean): void
		{
			this.setAutoHide(isEditMode);
			this.focusOnTextarea();
		},
		async note(): Promise<void>
		{
			await this.mountedPromise;

			this.$refs.popup.getPopupInstance().setHeight(0);

			const minHeight = 120;
			const maxHeight = 280;
			const height = this.$refs.textarea.scrollHeight + 45;
			const popupHeight = Math.min(maxHeight, Math.max(minHeight, height));

			this.$refs.popup.getPopupInstance().setHeight(popupHeight);
			this.adjustPosition();
		},
	},
	components: {
		Popup,
		Button,
	},
	template: `
		<Popup
			:id="popupId"
			:config="config"
			ref="popup"
			@close="closePopup"
		>
			<div
				class="booking-booking-note-popup-content"
				data-element="booking-note-popup"
				:data-id="bookingId"
				@mousedown="onMouseDown"
			>
				<div
					class="booking-booking-note-popup-title"
					data-element="booking-note-popup-title"
					:data-id="bookingId"
				>
					{{ loc('BOOKING_BOOKING_NOTE_TITLE') }}
				</div>
				<textarea
					v-model="note"
					class="booking-booking-note-popup-textarea"
					:placeholder="loc('BOOKING_BOOKING_NOTE_HINT')"
					:disabled="!isEditMode"
					data-element="booking-note-popup-textarea"
					:data-id="bookingId"
					:data-disabled="!isEditMode"
					ref="textarea"
				></textarea>
				<div v-if="isEditMode" class="booking-booking-note-popup-buttons">
					<Button
						:dataset="{id: bookingId, element: 'booking-note-popup-save'}"
						:text="loc('BOOKING_BOOKING_NOTE_SAVE')"
						:size="ButtonSize.EXTRA_SMALL"
						:color="ButtonColor.PRIMARY"
						@click="saveNote"
					/>
					<Button
						:dataset="{id: bookingId, element: 'booking-note-popup-cancel'}"
						:text="loc('BOOKING_BOOKING_NOTE_CANCEL')"
						:size="ButtonSize.EXTRA_SMALL"
						:color="ButtonColor.LINK"
						@click="closePopup"
					/>
				</div>
			</div>
		</Popup>
	`,
};
