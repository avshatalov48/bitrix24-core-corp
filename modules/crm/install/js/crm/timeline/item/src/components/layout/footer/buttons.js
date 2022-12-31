import {Button} from '../button';

export const Buttons = {
	components: {
		Button,
	},
	props: {
		items: {
			type: Array,
			required: false,
			default: () => [],
		},
	},
	methods: {
		getButtonById(buttonId: string): ?Object
		{
			const buttons = this.$refs.buttons;

			return this.items.reduce((found, button, index) =>
			{
				if (found)
				{
					return found;
				}
				if (button.id === buttonId)
				{
					return buttons[index];
				}

				return null;
			}, null);
		},
	},
	template:
		`
			<div class="crm-timeline__card-action_buttons">
				<Button class="crm-timeline__card-action-btn" v-for="item in items" v-bind="item" ref="buttons" />
			</div>
		`
};
