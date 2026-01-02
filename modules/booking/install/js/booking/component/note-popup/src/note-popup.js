import { Event } from 'main.core';
import { PopupManager } from 'main.popup';
import { ref } from 'ui.vue3';
import type { Popup as IPopup, PopupOptions } from 'main.popup';

import { Resolvable } from 'booking.lib.resolvable';
import { Popup } from 'booking.component.popup';
import { Button, ButtonSize, ButtonColor } from 'booking.component.button';

import './note-popup.css';

type NotePopupSetupData = {
	buttonColor: ButtonColor,
	buttonSize: ButtonSize,
	note: string,
	mountedPromise: Resolvable,
};

export type NotePopupSavePayload = {
	id: number | string,
	note: string,
}

export const NotePopup = {
	name: 'NotePopup',
	emits: ['save', 'close'],
	props: {
		id: {
			type: [Number, String],
			required: true,
		},
		text: {
			type: String,
			default: '',
		},
		bindElement: {
			type: Function,
			required: true,
		},
		isEditMode: {
			type: Boolean,
			required: true,
		},
		dataId: {
			type: [String, Number],
			default: '',
		},
		dataElementPrefix: {
			type: String,
			default: '',
		},
	},
	setup(): NotePopupSetupData
	{
		const buttonSize = ButtonSize;
		const buttonColor = ButtonColor;
		const note = ref('');
		const mountedPromise = new Resolvable();

		return {
			buttonColor,
			buttonSize,
			mountedPromise,
			note,
		};
	},
	beforeCreate(): void
	{
		const popupId = `booking-booking-note-popup-${this.id}`;

		PopupManager.getPopups()
			.filter((popup: IPopup) => popup.getId() === popupId)
			.forEach((popup: IPopup) => popup.destroy());
	},
	created(): void
	{
		this.note = this.text;
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
		popupId(): string
		{
			return `booking-booking-note-popup-${this.id}`;
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
		closePopup(): void
		{
			this.$emit('close');
		},
		saveNote(): void
		{
			if (this.text !== this.note)
			{
				this.$emit('save', {
					id: this.id,
					note: this.note,
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
		Button,
		Popup,
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
				:data-element="dataElementPrefix + '-note-popup'"
				:data-id="dataId"
				@mousedown="onMouseDown"
			>
				<div
					class="booking-booking-note-popup-title"
					:data-element="dataElementPrefix + '-note-popup-title'"
					:data-id="dataId"
				>
					{{ loc('BOOKING_BOOKING_NOTE_TITLE') }}
				</div>
				<textarea
					v-model.trim="note"
					class="booking-booking-note-popup-textarea"
					:placeholder="loc('BOOKING_BOOKING_NOTE_HINT')"
					:disabled="!isEditMode"
					:data-element="dataElementPrefix + '-note-popup-textarea'"
					:data-id="dataId"
					:data-disabled="!isEditMode"
					ref="textarea"
				></textarea>
				<div v-if="isEditMode" class="booking-booking-note-popup-buttons">
					<slot name="buttons">
						<Button
							:dataset="{id: dataId, element: dataElementPrefix + '-note-popup-save'}"
							:text="loc('BOOKING_BOOKING_NOTE_SAVE')"
							:size="buttonSize.EXTRA_SMALL"
							:color="buttonColor.PRIMARY"
							@click="saveNote"
						/>
						<Button
							:dataset="{id: dataId, element: dataElementPrefix + '-note-popup-cancel'}"
							:text="loc('BOOKING_BOOKING_NOTE_CANCEL')"
							:size="buttonSize.EXTRA_SMALL"
							:color="buttonColor.LINK"
							@click="closePopup"
						/>
					</slot>
				</div>
			</div>
		</Popup>
	`,
};
