import { mapGetters } from 'ui.vue3.vuex';
import { ClientPopup } from 'booking.component.client-popup';
import { AhaMoment, HelpDesk, Model } from 'booking.const';
import { bookingService } from 'booking.provider.service.booking-service';
import { ahaMoments } from 'booking.lib.aha-moments';
import { limit } from 'booking.lib.limit';
import { isRealId } from 'booking.lib.is-real-id';
import type { ClientData } from 'booking.model.clients';
import './add-client.css';

export const AddClient = {
	props: {
		bookingId: {
			type: [Number, String],
			required: true,
		},
		resourceId: {
			type: Number,
			required: true,
		},
		expired: {
			type: Boolean,
			default: false,
		},
	},
	data(): Object
	{
		return {
			showPopup: false,
		};
	},
	mounted(): void
	{
		if (isRealId(this.bookingId))
		{
			ahaMoments.setBookingForAhaMoment(this.bookingId);
		}

		if (ahaMoments.shouldShow(AhaMoment.AddClient, { bookingId: this.bookingId }))
		{
			void this.showAhaMoment();
		}
	},
	computed: mapGetters({
		providerModuleId: `${Model.Clients}/providerModuleId`,
		isFeatureEnabled: `${Model.Interface}/isFeatureEnabled`,
	}),
	methods: {
		clickHandler(): void
		{
			if (!this.isFeatureEnabled)
			{
				limit.show();

				return;
			}

			this.showPopup = true;
		},
		async addClientsToBook(clients: ClientData[]): Promise<void>
		{
			const booking = this.$store.getters[`${Model.Bookings}/getById`](this.bookingId);
			await bookingService.update({
				id: booking.id,
				clients,
			});
		},
		async showAhaMoment(): Promise<void>
		{
			await ahaMoments.show({
				id: 'booking-add-client',
				title: this.loc('BOOKING_AHA_ADD_CLIENT_TITLE'),
				text: this.loc('BOOKING_AHA_ADD_CLIENT_TEXT'),
				article: HelpDesk.AhaAddClient,
				target: this.$refs.button,
			});

			ahaMoments.setShown(AhaMoment.AddClient);
		},
	},
	components: {
		ClientPopup,
	},
	template: `
		<div
			v-if="providerModuleId"
			class="booking-booking-booking-add-client"
			:class="{ '--expired': expired }"
			data-element="booking-add-client-button"
			:data-id="bookingId"
			:data-resource-id="resourceId"
			ref="button"
			@click="clickHandler"
		>
			{{ loc('BOOKING_BOOKING_PLUS_CLIENT') }}
		</div>
		<ClientPopup
			v-if="showPopup"
			:bindElement="this.$refs.button"
			:offset-top="-100"
			:offset-left="this.$refs.button.offsetWidth + 10"
			@create="addClientsToBook"
			@close="showPopup = false"
		/>
	`,
};
