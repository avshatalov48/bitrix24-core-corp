import { Loader } from 'main.loader';
import { mapGetters } from 'ui.vue3.vuex';

import { Model } from 'booking.const';
import { calendarDataService } from 'booking.provider.service.calendar-data-service';

import { BookingEventPopupClient } from './client/client';
import { BookingEventPopupNote } from './note/note';
import { ResourceEntitiesList } from './entities-list/resource-entities-list';
import { ServicesEntitiesList } from './entities-list/services-entities-list';

// @vue/component
export const App = {
	name: 'BookingEventPopupApp',
	components: {
		BookingEventPopupClient,
		BookingEventPopupNote,
		ResourceEntitiesList,
		ServicesEntitiesList,
	},
	props: {
		bookingId: {
			type: Number,
			required: true,
		},
	},
	data(): { fetching: boolean }
	{
		return {
			fetching: false,
		};
	},
	computed: {
		...mapGetters({
			note: `${Model.BookingInfo}/note`,
		}),
	},
	watch: {
		fetching: {
			handler(fetching): void
			{
				if (fetching)
				{
					this.showLoader();
				}
				else
				{
					this.hideLoader();
				}
			},
			immediate: true,
		},
	},
	created(): void
	{
		this.loader = new Loader();
	},
	mounted()
	{
		void this.fetchBookingInfo();
	},
	methods: {
		async fetchBookingInfo(): Promise<void>
		{
			try
			{
				this.fetching = true;
				await calendarDataService.loadBookingInfo(this.bookingId);
			}
			catch (error)
			{
				console.error('BookingEventPopup. Get data error', error);
			}
			finally
			{
				this.fetching = false;
			}
		},
		showLoader(): void
		{
			void this.loader?.show(this.$refs.app);
		},
		hideLoader(): void
		{
			void this.loader?.hide(this.$refs.app);
		},
	},
	template: `
		<div ref="app" class="booking-event-popup__content">
			<div class="booking-event-popup__title">{{ loc('BOOKING_EVENT_POPUP_TITLE') }}</div>
			<BookingEventPopupClient v-if="!fetching"/>
			<BookingEventPopupNote v-if="note"/>
			<div class="booking-event-popup__resources">
				<ResourceEntitiesList/>
				<ServicesEntitiesList/>
			</div>
		</div>
	`,
};
