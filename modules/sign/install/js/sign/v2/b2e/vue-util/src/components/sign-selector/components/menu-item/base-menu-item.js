import { BIcon, Set } from 'ui.icon-set.api.vue';
import './style.css';

// @vue/component
export const BaseMenuItem = {
	name: 'BaseMenuItem',
	components: {
		BIcon,
	},
	props: {
		title: {
			type: String,
			required: true,
		},
		selected: {
			type: Boolean,
			required: false,
		},
	},

	computed: {
		set: (): typeof Set => Set,
	},

	template: `
		<div class="sign-b2e-vue-util-sign-selector__menu-item">
			<div class="sign-b2e-vue-util-sign-selector__menu-item__title">
				{{ title }}
			</div>
			<BIcon v-if="selected"
				style="margin-left: auto; margin-right: 16px"
				:name="set.CHECK"
				:size="18"
				color="#2066B0"
			/>
		</div>
	`,
};
