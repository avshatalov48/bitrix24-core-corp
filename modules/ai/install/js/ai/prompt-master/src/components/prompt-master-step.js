import '../css/prompt-master-step.css';
import { PromptMasterProgress } from './prompt-master-progress';
import { PromptMasterAlertMessage } from './prompt-master-alert-message';

export const PromptMasterStep = {
	components: {
		PromptMasterProgress,
		PromptMasterAlertMessage,
	},
	props: {
		suptitle: {
			type: String,
			required: true,
			default: '',
		},
		title: {
			type: String,
			required: true,
			default: '',
		},
		stepIndex: {
			type: Number,
			required: true,
			default: 0,
		},
		stepsCount: {
			type: Number,
			required: true,
			default: 3,
		},
		alertMessage: {
			type: String,
			required: false,
			default: '',
		},
	},
	template: `
		<div class="ai__prompt-master-step">
			<header class="ai__prompt-master-step_header">
				<span class="ai__prompt-master-step_suptitle">{{ suptitle }}</span>
				<h4 class="ai__prompt-master-step_title">{{ title }}</h4>
				<div class="ai__prompt-master-step__progress">
					<PromptMasterProgress :current-step="stepIndex" :steps-count="stepsCount" />
				</div>
				<div v-if="alertMessage" class="ai__prompt-master-step__alert-message">
					<PromptMasterAlertMessage :text="alertMessage"></PromptMasterAlertMessage>
				</div>
			</header>
			<main class="ai__prompt-master-step_content">
				<slot name="content"></slot>
			</main>
			<footer
				class="ai__prompt-master-step_footer"
			>
				<slot name="footer"></slot>
			</footer>
		</div>
	`,
};
