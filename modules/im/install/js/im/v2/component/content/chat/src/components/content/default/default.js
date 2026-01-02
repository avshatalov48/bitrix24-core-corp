import { BaseChatContent } from 'im.v2.component.content.elements';

import { DefaultHeader } from './components/header';
import { DefaultTextarea } from './components/textarea';

export const DefaultChatContent = {
	name: 'DefaultChatContent',
	components: { BaseChatContent, DefaultHeader, DefaultTextarea },
	props:
	{
		dialogId: {
			type: String,
			required: true,
		},
	},
	template: `
		<BaseChatContent :dialogId="dialogId">
			<template #header>
				<DefaultHeader :dialogId="dialogId" :key="dialogId" />
			</template>
			<template #textarea="{ onTextareaMount }">
				<DefaultTextarea :dialogId="dialogId" :key="dialogId" @onTextareaMount="onTextareaMount" />
			</template>
		</BaseChatContent>
	`,
};
