import { Loc } from 'main.core';
import { ButtonColor, Button } from 'ui.buttons';

export const ButtonPanel = {
	name: 'ButtonPanel',

	emits: ['save'],

	mounted()
	{
		const saveButton = new Button({
			text: Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_SAVE_BUTTON_TITLE'),
			color: ButtonColor.DANGER,
			round: true,
			noCaps: true,
			onclick: () => {
				this.$emit('save');
			},
		});
		saveButton.renderTo(this.$refs.container);
	},

	template: `
		<div ref="container"></div>
	`,
};
