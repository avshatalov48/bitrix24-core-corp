import { ExtraResourcesInfo } from 'booking.component.actions-popup';

// @vue/component
export const BookingExtraResourcesInfo = {
	name: 'BookingExtraResourcesInfo',
	components: {
		ExtraResourcesInfo,
	},
	props: {
		bookingId: {
			type: [Number, String],
			required: true,
		},
	},
	emits: ['freeze', 'unfreeze'],
	template: `
		<ExtraResourcesInfo
			:id="bookingId"
			@open="$emit('freeze')"
			@close="$emit('unfreeze')"
			@freeze="$emit('freeze')"
			@unfreeze="$emit('unfreeze')"
		/>
	`,
};
