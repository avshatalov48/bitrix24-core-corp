import { Button, ButtonSize, ButtonColor } from 'booking.component.button';
import type { IStep } from '../../presenter';

export const NextButton = {
	name: 'NextButton',
	components: {
		UiButton: Button,
	},
	props: {
		step: {
			type: Number,
			required: true,
		},
		steps: {
			type: Array,
			required: true,
		},
		disabled: Boolean,
		waiting: Boolean,
	},
	computed: {
		currentStep(): IStep
		{
			return this.steps[this.step - 1];
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
	template: `
		<UiButton
			:text="currentStep.labelNext"
			:title="currentStep.labelNext"
			:size
			:color
			:disabled
			:waiting
			@click="currentStep.next()"
		/>
	`,
};
