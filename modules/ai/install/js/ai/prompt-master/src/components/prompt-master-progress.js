import '../css/prompt-master-progress.css';

export const PromptMasterProgress = {
	props: {
		stepsCount: {
			type: Number,
			required: true,
			default: 1,
		},
		currentStep: {
			type: Number,
			required: true,
			default: 1,
		},
	},
	methods: {
		getProgressStepClassname(isPassedStep: boolean): Object {
			return {
				'ai__prompt-master-progress_step': true,
				'--passed': isPassedStep,
			};
		},
	},
	template: `
		<div class="ai__prompt-master-progress">
			<div
				v-for="(_, step) in stepsCount"
				:class="getProgressStepClassname(step < currentStep)"
			>
			</div>
		</div>
	`,
};
