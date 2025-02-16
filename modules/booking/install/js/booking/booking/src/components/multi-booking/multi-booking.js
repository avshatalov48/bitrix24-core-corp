import { Loc, Text } from 'main.core';
import { mapGetters } from 'ui.vue3.vuex';

import { Model } from 'booking.const';
import { bookingService } from 'booking.provider.service.booking-service';
import type { BookingModel } from 'booking.model.bookings';
import type { ClientModel } from 'booking.model.clients';

import { BookingMultipleButton } from './booking-multiple-button/booking-multiple-button';
import { MultiBookingItemsList } from './multi-booking-items-list/multi-booking-items-list';
import { AddClientButton } from './add-client-button/add-client-button';
import { CancelButton } from './cancel-button/cancel-button';
import './multi-booking.css';

export const MultiBooking = {
	name: 'MultiBooking',
	data(): { fetching: boolean, clients: ClientModel[] }
	{
		return {
			fetching: false,
			clients: [],
		};
	},
	computed: mapGetters({
		selectedCells: `${Model.Interface}/selectedCells`,
		timezone: `${Model.Interface}/timezone`,
	}),
	methods: {
		removeSelected(id: string): void
		{
			if (Object.hasOwnProperty.call(this.selectedCells, id))
			{
				this.$store.dispatch(`${Model.Interface}/removeSelectedCell`, this.selectedCells[id]);
			}
		},
		async book(): void
		{
			const bookings = this.getBookings();
			if (bookings.length === 0)
			{
				return;
			}

			this.fetching = true;
			const bookingList = await bookingService.addList(bookings);
			this.fetching = false;

			this.showNotification(bookingList);

			await this.closeMultiBooking();
		},
		getBookings(): BookingModel[]
		{
			return Object.values(this.selectedCells).map((cell) => ({
				id: `tmp-id-${Date.now()}-${Math.random()}`,
				dateFromTs: cell.fromTs,
				dateToTs: cell.toTs,
				name: this.loc('BOOKING_BOOKING_DEFAULT_BOOKING_NAME'),
				resourcesIds: [cell.resourceId],
				timezoneFrom: this.timezone,
				timezoneTo: this.timezone,
				clients: this.clients,
			}));
		},
		showNotification(bookingList)
		{
			const bookingQuantity = bookingList.length;

			const balloon = BX.UI.Notification.Center.notify({
				id: Text.getRandom(),
				content: Loc.getMessagePlural('BOOKING_MULTI_CREATED', bookingQuantity, {
					'#QUANTITY#': bookingQuantity,
				}),
				actions: [
					{
						title: this.loc('BOOKING_MULTI_CREATED_CANCEL'),
						events: {
							click: () => this.reset(bookingList, balloon),
						},
					},
				],
			});
		},
		async reset(bookingList, balloon): Promise<void>
		{
			await bookingService.deleteList(bookingList.map(({ id }) => id));
			balloon?.close();
		},
		async closeMultiBooking()
		{
			await this.$store.dispatch(`${Model.Interface}/clearSelectedCells`);
		},
	},
	components: {
		BookingMultipleButton,
		MultiBookingItemsList,
		AddClientButton,
		CancelButton,
	},
	template: `
		<Teleport to="#uiToolbarContainer" defer>
			<div class="booking--multi-booking--bar">
				<BookingMultipleButton :fetching @book="book"/>
				<MultiBookingItemsList @remove-selected="removeSelected"/>
				<div class="booking--multi-booking--divider-vertical"></div>
				<AddClientButton v-model="clients"/>
				<div class="booking--multi-booking--space"></div>
				<div class="booking--multi-booking--close">
					<div class="booking--multi-booking--divider-vertical"></div>
					<CancelButton @click="closeMultiBooking" />
				</div>
			</div>
		</Teleport>
	`,
};
