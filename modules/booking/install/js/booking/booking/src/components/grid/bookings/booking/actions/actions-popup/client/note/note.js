import { mapGetters } from 'ui.vue3.vuex';
import { Model } from 'booking.const';
import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';

import type { BookingModel } from 'booking.model.bookings';
import { NotePopup } from '../../../../note/note-popup/note-popup';
import './note.css';

export const Note = {
	emits: ['popupShown', 'popupClosed'],
	props: {
		bookingId: {
			type: [Number, String],
			required: true,
		},
	},
	data(): Object
	{
		return {
			IconSet,
			isPopupShown: false,
			isEditMode: false,
		};
	},
	computed: {
		...mapGetters({
			isFeatureEnabled: `${Model.Interface}/isFeatureEnabled`,
		}),
		booking(): BookingModel
		{
			return this.$store.getters['bookings/getById'](this.bookingId);
		},
		hasNote(): boolean
		{
			return Boolean(this.booking.note);
		},
	},
	methods: {
		onMouseEnter(): void
		{
			this.showNoteTimeout = setTimeout(() => this.showViewPopup(), 100);
		},
		onMouseLeave(): void
		{
			clearTimeout(this.showNoteTimeout);
			this.closeViewPopup();
		},
		showViewPopup(): void
		{
			if (this.isPopupShown || !this.hasNote)
			{
				return;
			}

			this.isEditMode = false;
			this.showPopup();
		},
		closeViewPopup(): void
		{
			if (this.isEditMode)
			{
				return;
			}

			this.closePopup();
		},
		showEditPopup(): void
		{
			this.isEditMode = true;
			this.showPopup();
		},
		closeEditPopup(): void
		{
			if (!this.isEditMode)
			{
				return;
			}

			this.closePopup();
		},
		showPopup(): void
		{
			this.isPopupShown = true;
			this.$emit('popupShown');
		},
		closePopup(): void
		{
			this.isPopupShown = false;
			this.$emit('popupClosed');
		},
	},
	components: {
		NotePopup,
		Icon,
	},
	template: `
		<div
			class="booking-actions-popup__item-client-note"
			data-element="booking-menu-note"
			:data-booking-id="bookingId"
			:data-has-note="hasNote"
			:class="{'--empty': !hasNote}"
			ref="note"
		>
			<div
				class="booking-actions-popup__item-client-note-inner"
				data-element="booking-menu-note-add"
				:data-booking-id="bookingId"
				@mouseenter="onMouseEnter"
				@mouseleave="onMouseLeave"
				@click="() => hasNote ? showViewPopup() : showEditPopup()"
			>
				<template v-if="hasNote">
					<div
						class="booking-actions-popup__item-client-note-text"
						data-element="booking-menu-note-text"
						:data-booking-id="bookingId"
					>
						{{ booking.note }}
					</div>
					<div
						v-if="isFeatureEnabled"
						class="booking-actions-popup__item-client-note-edit"
						data-element="booking-menu-note-edit"
						:data-booking-id="bookingId"
						@click="showEditPopup"
					>
						<Icon :name="IconSet.PENCIL_40"/>
					</div>
				</template>
				<template v-else>
					<Icon :name="IconSet.PLUS_20"/>
					<div class="booking-actions-popup__item-client-note-text">
						{{ loc('BB_ACTIONS_POPUP_ADD_NOTE') }}
					</div>
				</template>
			</div>
		</div>
		<NotePopup
			v-if="isPopupShown"
			:isEditMode="isEditMode && isFeatureEnabled"
			:bookingId="bookingId"
			:bindElement="() => $refs.note"
			@close="closeEditPopup"
		/>
	`,
};
