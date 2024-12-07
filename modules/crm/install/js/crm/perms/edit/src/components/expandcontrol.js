import 'ui.design-tokens';
import '../css/expandcontrol.css';

export const ExpandControl = {
	name: 'ExpandControl',
	props: {
		entity: Object,
		isExpanded: Boolean,
	},
	emits: ['toggle'],
	methods: {
		toggleState() {
			this.$emit('toggle');
		},
	},
	template: `
		<div
			:class="{'--expanded': isExpanded}"
			class="bx-crm-perms-edit-expand_control"
			@click="toggleState">
		</div>
	`,
};
