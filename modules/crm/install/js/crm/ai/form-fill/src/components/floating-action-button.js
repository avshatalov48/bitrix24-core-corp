import '../css/floating-action-button.css';
import { mapGetters } from 'ui.vue3.vuex';

export const FloatingActionButton = {
	name: 'FloatingActionButton',
	computed: {
		...mapGetters({
			count: 'getNotVisibleUnresolvedCount',
		}),
		showCounter(): boolean {
			return this.count > 0;
		},
	},
	methods: {
		click() {
			this.$Bitrix.eventEmitter.emit('crm:ai:form-fill:scroll-to-next', {});
		},
	},
	template: `
		<div @click="click" class="bx-crm-ai-merge-fields-fab">
			<div
				v-if="showCounter"
				class="bx-crm-ai-merge-fields-fab_counter"
			>{{count}}</div>
			<i class="bx-crm-ai-merge-fields-fab_icon"></i>
		</div>
	`,
};
