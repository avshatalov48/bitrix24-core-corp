import { mapGetters } from 'ui.vue3.vuex';
import { Model } from 'booking.const';
import { ActionsPopup } from './actions-popup/actions-popup';

export const Actions = {
	name: 'BookingActions',
	props: {
		bookingId: {
			type: [Number, String],
			required: true,
		},
		resourceId: {
			type: Number,
			required: true,
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
		if (this.isEditingBookingMode && this.editingBookingId === this.bookingId)
		{
			this.showPopup = true;
		}
	},
	computed: mapGetters({
		editingBookingId: `${Model.Interface}/editingBookingId`,
		isEditingBookingMode: `${Model.Interface}/isEditingBookingMode`,
	}),
	methods: {
		clickHandler(): void
		{
			this.showPopup = true;
		},
	},
	components: {
		ActionsPopup,
	},
	template: `
		<div 
			ref="node"
			class="booking-booking-booking-actions"
			data-element="booking-booking-actions-button"
			:data-id="bookingId"
			:data-resource-id="resourceId"
			@click="clickHandler"
		>
			<div class="booking-booking-booking-actions-inner">
				<div class="ui-icon-set --chevron-down"></div>
			</div>
		</div>
		<ActionsPopup
			v-if="showPopup"
			:bookingId="bookingId"
			:bindElement="this.$refs.node"
			@close="showPopup = false"
		/>
	`,
};
