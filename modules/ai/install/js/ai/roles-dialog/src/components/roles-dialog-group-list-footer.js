import { EventEmitter } from 'main.core.events';
import '../css/roles-dialog-group-list-footer.css';

export const RolesDialogGroupListFooterEvents = {
	CHOOSE_STANDARD_ROLE: 'AI.RolesDialog.GroupListFooter:ChooseStandardRole',
};

export const RolesDialogGroupListFooter = {
	methods: {
		handleClick(): void {
			EventEmitter.emit(document, RolesDialogGroupListFooterEvents.CHOOSE_STANDARD_ROLE);
		},
	},
	template: `
		<button @click="handleClick" class="ai__roles-dialog_standard-group-btn">
			<span class="ai__roles-dialog_standard-group-btn-text">
				{{ $Bitrix.Loc.getMessage('AI_COPILOT_ROLES_USE_STANDARD_ROLE') }}
			</span>
		</button>
	`,
};
