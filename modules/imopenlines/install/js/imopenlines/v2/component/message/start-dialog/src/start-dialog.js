import { Parser } from 'im.v2.lib.parser';
import { BaseMessage } from 'im.v2.component.message.base';

import './css/start-dialog.css';

// @vue/component
export const StartDialogMessage = {
	name: 'StartDialogMessage',
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
	computed:
	{
		formattedText(): string
		{
			return Parser.decodeMessage(this.item);
		},
	},
	template: `
		<BaseMessage
			:dialogId="dialogId"
			:item="item"
			:withTitle="false"
			:withBackground="false"
			:withContextMenu="false"
			:withReactions="false"
			class="bx-imol-message-start-dialog__container"
		>
			<div class="bx-imol-message-start-dialog__text" v-html="formattedText"></div>
		</BaseMessage>
	`,
};
