import { BitrixVueComponentProps } from 'ui.vue3';
import { Button } from 'ui.buttons';

import { Control } from '../../layout/control/control';

import './button-control.css';

export const ButtonControl: BitrixVueComponentProps = {
	name: 'ButtonControl',

	components: {
		Control,
	},

	props: {
		buttonOptions: {
			type: Object,
			required: true,
		},
	},

	mounted(): void
	{
		(new Button(this.buttonOptions))
			.renderTo(this.$refs.button.$el)
		;
	},

	template: `
		<Control class="--button" ref="button" />
	`,
};
