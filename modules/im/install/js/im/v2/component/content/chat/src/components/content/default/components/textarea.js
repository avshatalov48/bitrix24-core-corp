import { ChatType } from 'im.v2.const';
import { ChatTextarea } from 'im.v2.component.textarea';
import { CopilotTextarea } from 'im.v2.component.content.copilot';

// @vue/component
export const DefaultTextarea = {
	name: 'DefaultTextarea',
	components: {
		ChatTextarea,
		CopilotTextarea,
	},
	props:
	{
		dialogId: {
			type: String,
			default: '',
		},
	},
	emits: ['onTextareaMount'],
	computed:
	{
		isCopilot(): boolean
		{
			return this.$store.getters['chats/get'](this.dialogId)?.type === ChatType.copilot;
		},
	},
	methods:
	{
		onTextareaMount()
		{
			this.$emit('onTextareaMount');
		},
	},
	template: `
		<CopilotTextarea v-if="isCopilot" :dialogId="dialogId" @mounted="onTextareaMount" />
		<ChatTextarea
			v-else
			:dialogId="dialogId"
			:withAudioInput="false"
			@mounted="onTextareaMount"
		/>
	`,
};
