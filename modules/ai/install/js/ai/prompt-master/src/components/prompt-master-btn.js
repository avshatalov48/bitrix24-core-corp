import { Button } from 'ui.buttons';

export const PromptMasterBtn = {
	props: {
		text: {
			type: String,
			required: true,
			default: '',
		},
		state: {
			type: String,
			required: false,
			default: '',
		},
		disabled: {
			type: Boolean,
			required: false,
			default: false,
		},
		color: {
			type: String,
			required: false,
			default: Button.Color.AI,
		},
	},
	computed: {
		buttonState(): string {
			return this.disabled ? Button.State.DISABLED : this.state;
		},
		buttonHtml(): string {
			const btn = new Button({
				color: this.color,
				size: Button.Size.MEDIUM,
				text: this.text,
				round: true,
				state: this.buttonState,
			});

			btn.setDisabled(this.disabled);

			return btn.render().outerHTML;
		},
	},
	template: `
		<div v-html="buttonHtml"></div>
	`,
};
