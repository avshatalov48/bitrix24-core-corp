import { Button } from 'ui.buttons';
import { Loc } from 'main.core';

export const AddButton = {
	name: 'AddButton',

	emits: [
		'click',
	],

	mounted()
	{
		this.getButton().renderTo(this.$refs.container);
	},

	methods: {
		getButton(): Button
		{
			return new Button({
				color: Button.Color.SUCCESS,
				text: Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_ADD_BUTTON_TITLE'),
				onclick: () => {
					this.$emit('click');
				},
			});
		},
	},

	template: `
		<div class="container" ref="container" style="margin: 20px 0;"></div>
	`,
};
