import 'ui.alerts';
import '../css/step-hint.css';

export const StepHint = {
	props: {
		hintClass: {
			type: String,
			required: false,
			default: 'ui-alert-primary',
		},
	},
	template: `
		<div class="ui-alert dataset-import-step__hint" :class="hintClass">
			<span class="ui-alert-message">
				<slot></slot>
			</span>
		</div>
	`,
};
