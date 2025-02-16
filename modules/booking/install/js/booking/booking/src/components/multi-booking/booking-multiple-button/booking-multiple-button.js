import { Loc } from 'main.core';
import { Button as UiButton, ButtonSize, ButtonColor } from 'booking.component.button';

export const BookingMultipleButton = {
	name: 'BookingMultipleButton',
	emits: ['book'],
	props: {
		fetching: Boolean,
	},
	computed: {
		text(): string
		{
			return this.loc('BOOKING_MULTI_BUTTON_LABEL');
		},
		size(): string
		{
			return ButtonSize.SMALL;
		},
		color(): string
		{
			return ButtonColor.SUCCESS;
		},
	},
	components: {
		UiButton,
	},
	template: `
		<UiButton
			:text
			:size
			:color
			:waiting="fetching"
			@click="$emit('book')"
		/>
	`,
};
