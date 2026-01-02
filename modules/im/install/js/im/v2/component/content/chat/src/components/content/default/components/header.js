import { ChatHeader } from 'im.v2.component.content.elements';
import { ChatType } from 'im.v2.const';
import { CopilotChatHeader } from 'im.v2.component.content.copilot';

// @vue/component
export const DefaultHeader = {
	name: 'DefaultHeader',
	components: {
		ChatHeader,
		CopilotChatHeader,
	},
	props:
	{
		dialogId: {
			type: String,
			default: '',
		},
	},
	computed:
	{
		isCopilot(): boolean
		{
			return this.$store.getters['chats/get'](this.dialogId)?.type === ChatType.copilot;
		},
	},
	template: `
		<CopilotChatHeader v-if="isCopilot" :dialogId="dialogId" />
		<ChatHeader v-else :dialogId="dialogId" />
	`,
};
