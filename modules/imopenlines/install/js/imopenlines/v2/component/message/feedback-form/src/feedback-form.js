import { BaseMessage } from 'im.v2.component.message.base';

import './css/feedback-form.css';

// @vue/component
export const FeedbackFormMessage = {
	name: 'FeedbackForm',
	components: { BaseMessage },
	props:
	{
		item: {
			type: Object,
			required: true,
		},
		dialogId: {
			type: String,
			required: true,
		},
	},
	methods:
	{
		loc(phraseCode: string): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode);
		},
	},
	template: `
		<BaseMessage
			:dialogId="dialogId"
			:item="item"
			:withTitle="false"
			:withBackground="false"
			class="bx-imol-message-feedback-form__container"
		>
			<div class="bx-imol-message-feedback-form__text">
				{{ loc('IMOL_MESSAGE_FEEDBACK_FORM_TEXT') }}
			</div>
		</BaseMessage>
	`,
};
