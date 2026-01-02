import { BIcon, Outline } from 'ui.icon-set.api.vue';
import { hint, type HintParams } from 'ui.vue3.directives.hint';

import { tooltip } from 'tasks.v2.component.elements.hint';

// @vue/component
export const ActionButton = {
	components: {
		BIcon,
	},
	directives: { hint },
	props: {
		title: {
			type: String,
			default: '',
		},
		iconName: {
			type: String,
			required: true,
		},
		iconColor: {
			type: String,
			default: '',
		},
	},
	setup(): Object
	{
		return {
			Outline,
		};
	},
	computed: {
		tooltip(): Function
		{
			return (): HintParams => tooltip({
				text: this.title,
				popupOptions: {
					offsetLeft: this.$el.offsetWidth / 2,
				},
			});
		},
	},
	template: `
		<button class="tasks-card-description-action-button" type="button" v-hint="tooltip">
			<BIcon
				:name="iconName"
				:color="iconColor"
				:hoverable="true"
				class="tasks-card-description-field-icon"
			/>
		</button>
	`,
};
