import { BaseChatContent, ChatHeader } from 'im.v2.component.content.elements';
import { ChatTextarea } from 'im.v2.component.textarea';

export const AiAssistantContent = {
	name: 'AiAssistantContent',
	components: { BaseChatContent, ChatHeader, ChatTextarea },
	props: {
		dialogId: {
			type: String,
			required: true,
		},
	},
	methods: {
		loc(phraseCode: string): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode);
		},
	},
	template: `
		<BaseChatContent :dialogId="dialogId">
			<template #textarea="{ onTextareaMount }">
				<ChatTextarea
					:dialogId="dialogId"
					:key="dialogId"
					:placeholder="loc('IM_CONTENT_AIASSISTANT_TEXTAREA_PLACEHOLDER')"
					:withMarket="false"
					:withEdit="false"
					:withUploadMenu="false"
					:withSmileSelector="false"
					@mounted="onTextareaMount"
				/>
			</template>
		</BaseChatContent>
	`,
};