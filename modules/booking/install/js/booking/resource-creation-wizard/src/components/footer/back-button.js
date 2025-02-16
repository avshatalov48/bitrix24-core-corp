import { Button, ButtonSize, ButtonColor } from 'booking.component.button';
import type { IStep } from '../../presenter';

export const BackButton = {
	name: 'BackButton',
	props: {
		step: {
			type: Number,
			required: true,
		},
		steps: {
			type: Array,
			required: true,
		},
	},
	computed: {
		buttonSize(): string
		{
			return ButtonSize.SMALL;
		},
		buttonColor(): string
		{
			return ButtonColor.LINK;
		},
		currentStep(): IStep
		{
			return this.steps[this.step - 1];
		},
	},
	components: {
		UiButton: Button,
	},
	template: `
		<UiButton
			class="resource-creation-wizard__back-button"
			:text="currentStep.labelBack"
			:title="currentStep.labelBack"
			:size="buttonSize"
			:color="buttonColor"
			@click="currentStep.back()"
		/>
	`,
};
