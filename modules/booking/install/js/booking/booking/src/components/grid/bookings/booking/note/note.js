import { mapGetters } from 'ui.vue3.vuex';
import { Model } from 'booking.const';
import type { BookingModel } from 'booking.model.bookings';
import { NotePopup } from './note-popup/note-popup';
import './note.css';

export const Note = {
	props: {
		bookingId: {
			type: [Number, String],
			required: true,
		},
		bindElement: {
			type: Function,
			required: true,
		},
	},
	data(): Object
	{
		return {
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
		showViewPopup(): void
		{
			if (this.isPopupShown || !this.hasNote)
			{
				return;
			}

			this.isEditMode = false;
			this.isPopupShown = true;
		},
		closeViewPopup(): void
		{
			if (this.isEditMode)
			{
				return;
			}

			this.isPopupShown = false;
		},
		showEditPopup(): void
		{
			this.isEditMode = true;
			this.isPopupShown = true;
		},
		closeEditPopup(): void
		{
			if (!this.isEditMode)
			{
				return;
			}

			this.isPopupShown = false;
		},
	},
	components: {
		NotePopup,
	},
	template: `
		<div class="booking-booking-booking-note">
			<div
				class="booking-booking-booking-note-button"
				:class="{'--has-note': hasNote}"
				data-element="booking-booking-note-button"
				:data-id="bookingId"
				@click="showEditPopup"
			>
				<div class="ui-icon-set --note"></div>
			</div>
		</div>
		<NotePopup
			v-if="isPopupShown"
			:isEditMode="isEditMode && isFeatureEnabled"
			:bookingId="bookingId"
			:bindElement="bindElement"
			@close="closeEditPopup"
		/>
	`,
};
