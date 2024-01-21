import { mapGetters } from 'ui.vue3.vuex';
import '../css/merger.css';
import type { ConflictField } from '../store/types';
import { MergeControl } from './merge-control';

export const Merger = {
	name: 'Merger',
	components: {
		MergeControl,
	},
	data() {
		return {
			isRootMounted: false,
		};
	},
	computed: {
		...mapGetters([
			'conflictFields',
			'eeControlPosition',
			'eeControlPositions',
			'getMainLayoutScrollPosition',
		]),
	},
	methods: {
		getControlTopOffset(field: ConflictField): number {
			return this.eeControlPositions.get(field.name, 0);
		},
	},
	mounted()
	{
		this.isRootMounted = true;
	},
	template: `
		<div ref="root" class="bx-crm-ai-merge-fields-merger ">
			<MergeControl
				v-if="isRootMounted"
				v-for="field in conflictFields" :key="field.name"
				class="bx-crm-ai-merge-fields-merger__field"
				:style="{top: getControlTopOffset(field) + 'px'}"
				:field="field"
				:tmp="getControlTopOffset(field)"
			></MergeControl>
		</div>
	`,
};
