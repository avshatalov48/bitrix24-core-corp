import { BIcon } from 'ui.icon-set.api.vue';
import { HoverPill } from 'tasks.v2.component.elements.hover-pill';

import './field-add.css';

export const FieldAdd = {
	components: {
		BIcon,
		HoverPill,
	},
	props: {
		icon: {
			type: String,
			required: true,
		},
	},
	template: `
		<HoverPill>
			<div class="b24-field-add">
				<BIcon :name="icon"/>
				<div>{{ loc('TASKS_V2_FIELD_ADD') }}</div>
			</div>
		</HoverPill>
	`,
};
