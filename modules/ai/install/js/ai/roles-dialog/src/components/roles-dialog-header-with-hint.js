import { Hint } from 'ui.vue3.components.hint';

import '../css/roles-dialog-header-with-hint.css';

export const RolesDialogHeaderWithHint = {
	components: {
		Hint,
	},
	props: {
		header: {
			type: String,
			required: false,
			default: '',
		},
		hint: {
			type: String,
			required: false,
			default: '',
		},
	},
	template: `
		<div class="ai__roles-dialog_header-with-hint">
			<span class="ai__roles-dialog_header-with-hint-text">
				{{ header }}
			</span>
			<span
				v-if="hint"
				class="ai__roles-dialog_header-with-hint-text-hint"
			>
				<Hint :text="hint" />
			</span>
		</div>
	`,
};
