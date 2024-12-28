import { Button, ButtonState } from 'ui.buttons';

export const RoleMasterBtn = {
	props: {
		text: String,
		state: {
			type: String,
			required: false,
			default: null,
		},
		color: {
			type: String,
			required: false,
			default: Button.Color.AI,
		},
	},
	computed: {
		buttonOuterHtmlString(): string {
			const button = new Button({
				text: this.text,
				state: this.state,
				color: this.color,
				round: true,
			});

			return button.render().outerHTML;
		},
		ButtonState(): ButtonState {
			return ButtonState;
		},
	},
	methods: {
		handleClick(e: MouseEvent): boolean {
			if (this.state === ButtonState.DISABLED)
			{
				e.preventDefault();
				e.stopImmediatePropagation();

				return false;
			}

			return true;
		},
	},
	template: '<div @click="handleClick" v-html="buttonOuterHtmlString"></div>',
};
