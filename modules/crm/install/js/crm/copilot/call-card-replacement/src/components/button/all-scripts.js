import { Button, ButtonSize, ButtonColor } from 'ui.buttons';

export const ALL_SCRIPTS_URL = '/crm/copilot-call-assessment/';

export const AllScripts = {
	name: 'AllScripts',

	computed: {
		classname(): Object {
			return {
				'crm-copilot__call-card-replacement-footer-btn': true,
				[Button.BASE_CLASS]: true,
				[ButtonSize.EXTRA_SMALL]: true,
				[ButtonColor.LIGHT]: true,
			};
		},
	},

	methods: {
		openAllScriptsPage(): void
		{
			window.open(ALL_SCRIPTS_URL, '_blank');
		},
	},

	template: `
		<button :class="classname" @click="openAllScriptsPage">
			<span class="ui-btn-text">
				{{ $Bitrix.Loc.getMessage('CRM_COPILOT_CALL_CARD_REPLACEMENT_ALL_SCRIPTS_BUTTON_TITLE') }}
			</span>
		</button>
	`,
};
