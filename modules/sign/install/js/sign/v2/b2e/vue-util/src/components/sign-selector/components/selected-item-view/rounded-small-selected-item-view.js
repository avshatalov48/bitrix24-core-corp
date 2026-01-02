import { BIcon, Set } from 'ui.icon-set.api.vue';
import './styles/rounded-small-selected-item-view.css';

// @vue/component
export const RoundedSmallSelectedItemView = {
	name: 'RoundedSmallSelectedItemView',
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
		<div class="sign-v2-b2e-vue-util-sign-selector__rounded-small-selected-item-view">
			<div class="sign-b2e-vue-util-sign-selector__rounded-small-selected-item-view__text">
				{{ title }}
			</div>
			<BIcon
				style="margin-left: 6px; margin-right: 5px;"
				color="#525C69"
				:name="set.CHEVRON_DOWN"
				:size="14"
			/>
		</div>
	`,
};
