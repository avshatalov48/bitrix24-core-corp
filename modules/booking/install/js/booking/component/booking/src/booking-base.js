// @vue/component

import './booking-base.css';

export const BookingBase = {
	name: 'BookingBase',
	emits: ['click'],
	props: {
		bookingStyle: {
			type: [String, Object, Array],
			default: '',
		},
		bookingClass: {
			type: [String, Object, Array],
			default: '',
		},
		disabled: {
			type: Boolean,
			default: false,
		},
		visible: {
			type: Boolean,
			default: true,
		},
		dataAttributes: {
			type: Object,
			default: () => ({}),
		},
	},
	methods: {
		onClick(event: PointerEvent): void
		{
			if (this.disabled)
			{
				this.$emit('click');
				event.stopPropagation();
			}
		},
	},
	template: `
		<div
			class="booking--booking-base"
			:style="bookingStyle"
			:class="bookingClass"
			v-bind="dataAttributes"
			@click.capture="onClick"
		>
			<div v-if="visible" class="booking--booking-base-padding booking-booking-booking-padding">
				<div class="booking--booking-base-inner booking-booking-booking-inner">
					<div class="booking--booking-base-content">
						<div class="booking--booking-base-content-row">
							<slot name="upper-content-row"/>
						</div>
						<div class="booking--booking-base-content-row --lower">
							<slot name="lower-content-row"/>
						</div>
					</div>
					<slot name="actions"></slot>
				</div>
			</div>
			<slot name="end"/>
		</div>
	`,
};
