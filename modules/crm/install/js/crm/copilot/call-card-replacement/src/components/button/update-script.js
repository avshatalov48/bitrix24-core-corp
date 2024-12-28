import { Button, ButtonSize, ButtonColor, ButtonStyle, ButtonState } from 'ui.buttons';
import { Router } from 'crm.router';
import { Loc } from 'main.core';
import { mapGetters } from 'ui.vue3.vuex';

export const UpdateScript = {
	name: 'UpdateScript',

	computed: {
		...mapGetters(['callAssessment', 'isScriptSelected']),

		classname(): Object {
			return {
				'crm-copilot__call-card-replacement-footer-btn': true,
				[Button.BASE_CLASS]: true,
				[ButtonSize.EXTRA_SMALL]: true,
				[ButtonColor.LIGHT_BORDER]: true,
				[ButtonStyle.ROUND]: true,
				[ButtonState.DISABLED]: !this.isScriptSelected,
			};
		},
	},

	methods: {
		showHint(): void
		{
			if (!this.isScriptSelected)
			{
				return;
			}

			top.BX.UI.Hint.popupParameters = {
				closeByEsc: true,
				autoHide: true,
			};

			const hintMessage = Loc.getMessage('CRM_COPILOT_CALL_CARD_REPLACEMENT_UPDATE_SCRIPT_BUTTON_TITLE_HINT');
			top.BX.UI.Hint.show(this.$refs.button, hintMessage, true);
		},

		// temporarily disabled
		openScriptDetailsSlider(): void
		{
			if (!this.isScriptSelected)
			{
				return;
			}

			const url = `/crm/copilot-call-assessment/details/${this.callAssessment?.id}/`;
			const options = {
				width: 700,
				cacheable: false,
			};

			void Router.openSlider(url, options);
		},
	},

	template: `
		<button ref="button" :class="classname" @click="showHint" :disabled="!isScriptSelected">
			<span class="ui-btn-text">
				{{ $Bitrix.Loc.getMessage('CRM_COPILOT_CALL_CARD_REPLACEMENT_UPDATE_SCRIPT_BUTTON_TITLE') }}
			</span>
		</button>
	`,
};
