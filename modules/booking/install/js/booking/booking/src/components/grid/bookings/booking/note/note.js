import { Model } from 'booking.const';
import { Note } from 'booking.component.booking';
import { NotePopup } from 'booking.component.note-popup';
import { bookingService } from 'booking.provider.service.booking-service';
import type { BookingModel } from 'booking.model.bookings';
import type { NotePopupSavePayload } from 'booking.component.note-popup';

import './note.css';

// @vue/component
export const BookingNote = {
	name: 'BookingNote',
	components: {
		Note,
		NotePopup,
	},
	props: {
		bookingId: {
			type: [Number, String],
			required: true,
		},
		bindElement: {
			type: Function,
			required: true,
		},
		visiblePopup: {
			type: Boolean,
			default: false,
		},
	},
	computed: {
		booking(): BookingModel
		{
			return this.$store.getters[`${Model.Bookings}/getById`](this.bookingId);
		},
	},
	watch: {
		visiblePopup(visible: boolean): void
		{
			if (visible)
			{
				this.$refs.note?.showViewPopup();
			}
			else
			{
				this.$refs.note?.closeViewPopup();
			}
		},
	},
	methods: {
		async saveBookingNote({ note }: NotePopupSavePayload): Promise<void>
		{
			await bookingService.update({
				id: this.booking.id,
				note,
			});
		},
	},
	template: `
		<Note
			ref="note"
			:id="bookingId"
			:note="booking.note"
			:bindElement
			className="booking-booking-booking-note-button"
			:dataId="bookingId"
			dataElementPrefix="booking"
			:dataAttributes="{
				'data-id': bookingId,
				'data-element': 'booking-booking-note-button',
			}"
			@save="saveBookingNote"
		/>
	`,
};
