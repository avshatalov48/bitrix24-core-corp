import { Alert, AlertSize, AlertColor, AlertIcon } from 'ui.alerts';

export const PromptMasterAlertMessage = {
	props: {
		text: {
			type: String,
			required: true,
			default: '',
		},
	},
	computed: {
		alertHtml(): String {
			const alert = new Alert({
				icon: AlertIcon.INFO,
				color: AlertColor.WARNING,
				size: AlertSize.XS,
				text: this.text,
			});

			return alert.render().outerHTML;
		},
	},
	template: `
		<div v-html="alertHtml"></div>
	`,
};
