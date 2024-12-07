import { RolesDialogHeaderWithHint } from './roles-dialog-header-with-hint';

import '../css/roles-dialog-group-list-header.css';

export const RolesDialogGroupListHeader = {
	components: {
		RolesDialogHeaderWithHint,
	},
	computed: {
		text(): string {
			return this.$Bitrix.Loc.getMessage('AI_COPILOT_ROLES_GROUP_LIST_HEADER_2');
		},
		hint(): string {
			return this.$Bitrix.Loc.getMessage('AI_COPILOT_ROLES_GROUP_LIST_HEADER_HINT_2');
		},
	},
	template: `
		<div class="ai__roles-dialog_group-list-header">
			<RolesDialogHeaderWithHint
				:header="text"
				:hint="hint"
			/>
		</div>
	`,
};
