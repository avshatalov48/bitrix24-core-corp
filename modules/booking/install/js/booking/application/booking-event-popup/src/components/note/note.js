import { mapGetters } from 'ui.vue3.vuex';
import { BIcon as UiIcon, Outline } from 'ui.icon-set.api.vue';

import { Model } from 'booking.const';

import './note.css';

// @vue/component
export const BookingEventPopupNote = {
	name: 'BookingEventPopupNote',
	components: {
		UiIcon,
	},
	setup(): { iconName: string }
	{
		const iconName = Outline.NOTE;

		return {
			iconName,
		};
	},
	data(): { showedMore: boolean }
	{
		return {
			showedMore: false,
		};
	},
	computed: {
		...mapGetters({
			note: `${Model.BookingInfo}/note`,
		}),
		more(): string
		{
			return this.loc('BOOKING_EVENT_POPUP_NOTE_MORE');
		},
		shortNote(): string
		{
			return this.showedMore ? this.note : this.note.slice(0, 100 - this.more.length).trimEnd();
		},
	},
	template: `
		<div class="booking-event-popup__person-info">
			<div class="booking-event-popup__person-info_icon">
				<UiIcon :name="iconName" :size="22" color="rgba(250, 167, 44, 1)"/>
			</div>
			<div class="booking-event-popup__person-info_text">
				<text>{{ shortNote }}</text>
				<span
					v-if="shortNote.length < note.length"
					class="booking-event-popup__person-info_text-more"
					@click="showedMore = true"
				>
					{{ more }}
				</span>
			</div>
		</div>
	`,
};
