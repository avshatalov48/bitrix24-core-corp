import { Cancel } from './cancel/cancel';
import './footer.css';

export const Footer = {
	name: 'Footer',
	emits: ['bookingCanceled', 'bookingConfirmed'],
	components: {
		Cancel,
	},
	props: {
		booking: {
			type: Object,
			required: true,
		},
		context: {
			type: String,
			required: true,

		},
	},
	data(): Object
	{
		return {};
	},
	template: `
		<div>
			<Cancel 
				:booking="booking"
				:context="context"
				@bookingCanceled="$emit('bookingCanceled')"
				@bookingConfirmed="$emit('bookingConfirmed')"
			/>
		</div>
	`,
};
