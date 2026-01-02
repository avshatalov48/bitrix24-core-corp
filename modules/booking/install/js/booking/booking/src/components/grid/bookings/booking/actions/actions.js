import { mapGetters } from 'ui.vue3.vuex';
import { Model } from 'booking.const';
import { BookingActionsPopup } from './actions-popup/actions-popup';

export type { ActionsPopupOptions } from './actions-popup/actions-popup';

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
		actionsPopupOptions: {
			type: Object,
			default: null,
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
		BookingActionsPopup,
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
		<BookingActionsPopup
			v-if="showPopup"
			:bookingId
			:bindElement="this.$refs.node"
			:resourceId
			:options="actionsPopupOptions"
			@close="showPopup = false"
		/>
	`,
};
