import { StepBlock } from './step-block';

export const AppSection = {
	props: {
		title: {
			type: String,
			required: false,
		},
		customClasses: {
			type: Array,
			required: false,
		},
	},
	components: {
		Step: StepBlock,
	},
	// language=Vue
	template: `
		<Step
			:title="title"
			:can-collapse="false"
			:custom-classes="customClasses"
		>
			<slot></slot>
		</Step>
	`,
};
