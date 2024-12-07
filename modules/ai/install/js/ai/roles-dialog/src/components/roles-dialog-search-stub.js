import { Loc } from 'main.core';

import '../css/roles-dialog-search-stub.css';
import { EventEmitter } from 'main.core.events';

export const RolesDialogSearchStubEvents = {
	CHOOSE_STANDARD_ROLE: 'AI.RolesDialog.RolesDialogSearchStub:ChooseStandardRole',
};

const textWithLink = Loc.getMessage('AI_COPILOT_ROLES_SEARCH_NO_RESULT_3', {
	'#LINK#': '<span @click.prevent="selectUniversalRole">',
	'#/LINK#': '</span>',
});

export const RolesDialogSearchStub = {
	methods: {
		selectUniversalRole(): void {
			EventEmitter.emit(document, RolesDialogSearchStubEvents.CHOOSE_STANDARD_ROLE);
		},
	},
	template: `
		<div class="ai__roles-dialog_search-stub">
			<div class="ai__roles-dialog_search-stub-content">
				<div class="ai__roles-dialog_search-stub-image"></div>
				<h3 class="ai__roles-dialog_search-stub-title">
					{{ $Bitrix.Loc.getMessage('AI_COPILOT_ROLES_SEARCH_NO_RESULT_TITLE') }}
				</h3>
				<div class="ai__roles-dialog_search-stub-text">
					${textWithLink}
				</div>
			</div>
		</div>
	`,
};
