import { Note } from 'booking.component.booking';
import { waitListService } from 'booking.provider.service.wait-list-service';
import type { NotePopupSavePayload } from 'booking.component.booking';
import type { WaitListItemModel } from 'booking.model.wait-list';

import './note.css';

// @vue/component
export const WaitListItemNote = {
	name: 'WaitListItemNote',
	components: {
		Note,
	},
	props: {
		/**
		 * @type {WaitListItemModel}
		 */
		waitListItem: {
			type: Object,
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
		async saveWaitListItemNote({ note }: NotePopupSavePayload): Promise<void>
		{
			const id = this.waitListItem.id;
			await waitListService.update({
				id,
				note,
			});
		},
	},
	template: `
		<Note
			ref="note"
			:id="waitListItem.id"
			:note="waitListItem.note"
			:bindElement
			className="booking--wait-list-item-note"
			:dataId="waitListItem.id"
			dataElementPrefix="wait-list-item"
			:dataAttributes="{
				'data-id': waitListItem.id,
				'data-element': 'booking-wait-list-item-note-button',
			}"
			@save="saveWaitListItemNote"
		/>
	`,
};
