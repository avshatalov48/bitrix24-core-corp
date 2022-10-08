import 'ui.design-tokens';
import 'ui.fonts.opensans';
import { BitrixVue } from 'ui.vue';
import 'ui.buttons';

import { Theme } from '../../../mixin/theme';

import './button-ask.css';

export const ButtonAskProps = Object.freeze({
	Type: {
		SECONDARY: 'secondary',
		PRIMARY: 'primary'
	}
});

export const ButtonAsk = BitrixVue.localComponent('imbot-support24-question-component-question-list-button-ask',{
	mixins: [Theme],
	props: {
		type: {
			type: String,
			default: ButtonAskProps.Type.SECONDARY,
		},
	},
	data: function() {
		return {
			lastQuestionTime: null,
		}
	},
	computed:
	{
		ButtonAskProps: () => ButtonAskProps,
		buttonClass()
		{
			const buttonClass = this.getClassWithTheme('bx-imbot-support24-question-list-button-ask-' + this.type);

			if (this.type === ButtonAskProps.Type.PRIMARY)
			{
				let largeButtonColor = this.darkTheme ? 'ui-btn-primary-dark' : 'ui-btn-primary';

				buttonClass[largeButtonColor] = true;
			}

			return buttonClass;
		},
	},
	methods:
	{
		askQuestion()
		{
			const tenSeconds = 5000;

			if (this.lastQuestionTime && (Date.now() - this.lastQuestionTime < tenSeconds))
			{
				return;
			}

			this.lastQuestionTime = Date.now();

			this.$emit('askQuestion');
		},
	},
	// language=Vue
	template: `
		<div class="bx-imbot-support24-question-list-button-ask">
			<div
				v-if="type === ButtonAskProps.Type.SECONDARY"
				:class="buttonClass"
				@click="askQuestion"
			>
				{{ $Bitrix.Loc.getMessage('IMBOT_SUPPORT24_QUESTION_BUTTON_ASK_NEW_TITLE') }}
			</div>

			<button
				v-if="type === ButtonAskProps.Type.PRIMARY"
				:class="buttonClass"
				@click="askQuestion"
				class="ui-btn ui-btn-sm ui-btn-round ui-btn-no-caps"
			>
				{{ $Bitrix.Loc.getMessage('IMBOT_SUPPORT24_QUESTION_BUTTON_ASK_TITLE') }}
			</button>
		</div>
	`
});