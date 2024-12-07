import { Runtime } from 'main.core';
import type { States as StatesType } from 'ui.entity-catalog';
import { mapWritableState } from 'ui.vue3.pinia';
import type { RolesDialogAnalytics } from '../roles-dialog-analytics';
import { RolesDialogHeaderWithHint } from './roles-dialog-header-with-hint';

import '../css/roles-dialog-content-header.css';

export function getRolesDialogContentHeader(States: StatesType, analytic: RolesDialogAnalytics): Object
{
	const sendSearchAnalyticLabel = (searchQuery: ?string) => {
		analytic.sendSearchLabel(searchQuery);
	};

	const debouncedSendSearchAnalyticLabel: Function = Runtime.debounce(sendSearchAnalyticLabel, 800);

	return {
		components: {
			RolesDialogHeaderWithHint,
		},
		computed: {
			...mapWritableState(States.useGlobalState, {
				searchQuery: 'searchQuery',
				searching: 'searchApplied',
			}),
			header(): string {
				return this.$Bitrix.Loc.getMessage('AI_COPILOT_ROLES_MAIN_CONTENT_HEADER');
			},
			hint(): string {
				return this.$Bitrix.Loc.getMessage('AI_COPILOT_ROLES_MAIN_CONTENT_HEADER_HINT');
			},
		},
		watch: {
			searchQuery() {
				if (this.searching)
				{
					debouncedSendSearchAnalyticLabel(this.searchQuery);
				}
			},
		},
		template: `
			<div class="ai__roles-dialog_main-content-header">
				<RolesDialogHeaderWithHint
					:header="header"
					:hint="hint"
				/>
			</div>
		`,
	};
}
