import { BIcon, Set } from 'ui.icon-set.api.vue';
import './styles/base-selected-item-view.css';

// @vue/component
export const BaseSelectedItemView = {
	name: 'BaseSelectedItemView',
	components: {
		BIcon,
	},
	props: {
		title: {
			type: String,
			required: false,
			default: '',
		},
	},
	computed: {
		set: (): typeof Set => Set,
	},
	template: `
		<div class="sign-v2-b2e-vue-util-sign-selector__selected-item-view">
			<div class="sign-b2e-vue-util-sign-selector__selected-item-view__text">
				{{ title }}
			</div>
			<BIcon
				style="margin-left: auto; margin-right: 10px;"
				:name="set.CHEVRON_DOWN"
				:size="20"
			/>
		</div>
	`,
};
