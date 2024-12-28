import { Loc } from 'main.core';

import 'ui.icon-set.main';
import { Button } from './button';

const ARTICLE_CODE = '23240682'

export const Navigation = {
	components: {
		Button,
	},

	props: {
		activeTabId: {
			type: String,
		},
		isEnabled: {
			type: Boolean,
		},
		readOnly: {
			type: Boolean,
			default: false,
		},
		showSaveButton: {
			type: Boolean,
			default: false,
		},
	},

	computed: {
		help(): string
		{
			return Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_NAVIGATION_HELP');
		},
		buttons(): string[]
		{
			return [
				'cancel',
				'back',
				'continue',
				'submit',
				'close',
				'update',
			];
		},
	},

	methods: {
		showArticle(): void
		{
			window.top.BX?.Helper?.show(`redirect=detail&code=${ARTICLE_CODE}`);
		},
	},

	template: `
		<div class="crm-copilot__call-assessment_navigation-container">
			<div class="crm-copilot__call-assessment_navigation-buttons-wrapper">
				<Button v-if="activeTabId !== 'settings'" id="continue" :is-enabled="isEnabled" />
				<Button v-if="activeTabId === 'settings' && !readOnly" id="submit" />
				<Button v-if="activeTabId === 'settings' && readOnly" id="close" />
				<Button v-if="activeTabId === 'about'" id="cancel" />
				<Button v-if="activeTabId !== 'about'" id="back" />
			</div>
			<div v-if="showSaveButton && activeTabId !== 'settings'">
				<Button id="update" :is-enabled="isEnabled" />
			</div>
			<div v-else class="crm-copilot__call-assessment_article" @click="showArticle">
				<span class="ui-icon-set --help"></span>
				{{ help }}
			</div>
		</div>
	`,
};
