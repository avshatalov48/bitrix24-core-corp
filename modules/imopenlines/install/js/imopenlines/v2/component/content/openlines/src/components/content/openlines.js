import { BaseChatContent } from 'im.v2.component.content.elements';
import { QueueType } from 'imopenlines.v2.lib.queue';
import { ChatDialog } from 'im.v2.component.dialog.chat';

import { JoinPanelContainer } from './components/join-panel/join-panel-container';
import { OpenLinesHeader } from './components/header/header';
import { OpenLinesTextarea } from './components/textarea/textarea';
import { OpenLinesMessageList } from './components/message-list/message-list';

import type { ImolModelSession } from 'imopenlines.v2.model';
import type { QueueTypeName } from 'imopenlines.v2.lib.queue';

// @vue/component
export const OpenLinesContent = {
	name: 'OpenLinesContent',
	components: {
		BaseChatContent,
		JoinPanelContainer,
		OpenLinesHeader,
		ChatDialog,
		OpenLinesTextarea,
		OpenLinesMessageList,
	},
	props:
	{
		dialogId: {
			type: String,
			required: true,
		},
	},
	computed:
	{
		queueType(): ?QueueTypeName
		{
			const session = this.getSessionByDialogId(this.dialogId);
			const queueType = this.$store.getters['queue/getTypeById'](session.queueId, true);

			return session ? queueType : null;
		},
		isQueueTypeAll(): boolean
		{
			return this.queueType === QueueType.all;
		},
	},
	methods:
	{
		getSessionByDialogId(dialogId: string): ?ImolModelSession
		{
			return this.$store.getters['recentOpenLines/getSession'](dialogId, true);
		},
	},
	template: `
		<BaseChatContent :dialogId="dialogId">
			<template #header>
				<OpenLinesHeader :dialogId="dialogId" :key="dialogId" :isQueueTypeAll="isQueueTypeAll" />
			</template>
			<template #dialog>
				<ChatDialog :dialogId="dialogId" :key="dialogId">
					<template #message-list>
						<OpenLinesMessageList :dialogId="dialogId" />
					</template>
				</ChatDialog>
			</template>
			<template #textarea="{ onTextareaMount }">
				<OpenLinesTextarea :dialogId="dialogId" @mounted="onTextareaMount"/>
			</template>
			<template #join-panel>
				<JoinPanelContainer :dialogId="dialogId" :isQueueTypeAll="isQueueTypeAll"/>
			</template>
		</BaseChatContent>
	`,
};
