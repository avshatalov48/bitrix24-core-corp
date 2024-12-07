import { BIcon } from 'ui.icon-set.api.vue';
import { Actions } from 'ui.icon-set.api.core';

import '../css/prompt-master-back-btn.css';

export const PromptMasterBackBtn = {
	components: {
		BIcon,
	},
	computed: {
		iconColor(): string {
			return getComputedStyle(document.body).getPropertyValue('--ui-color-copilot-secondary');
		},
		chevronLeftIconCode(): string {
			return Actions.CHEVRON_LEFT;
		},
	},
	template: `
		<button class="ai__prompt-master_back-btn">
			<BIcon :name="chevronLeftIconCode" :size="24"></BIcon>
			<span>{{ $Bitrix.Loc.getMessage('PROMPT_MASTER_BTN_PREV') }}</span>
		</button>
	`,
};
