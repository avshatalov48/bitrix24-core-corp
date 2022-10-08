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
	template:
		`
			<div class="crm-timeline__card-action_buttons">
				<Button class="crm-timeline__card-action-btn" v-for="item in items" v-bind="item" />
			</div>
		`
};
