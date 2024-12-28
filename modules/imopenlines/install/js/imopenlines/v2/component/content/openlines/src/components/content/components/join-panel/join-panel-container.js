import { Core } from 'im.v2.application.core';
import { Button as OpenLinesButton } from 'im.v2.component.elements';
import { StatusGroup } from 'imopenlines.v2.const';

import { ChatControlPanel } from './components/chat-control-panel';
import { JoinPanel } from './components/join-panel';

import './css/join-panel.css';

import type { ImModelChat } from 'im.v2.model';
import type { ImolModelSession } from 'imopenlines.v2.model';

// @vue/component
export const JoinPanelContainer = {
	name: 'JoinPanelContainer',
	components: { OpenLinesButton, ChatControlPanel, JoinPanel },
	props:
	{
		dialogId: {
			type: String,
			required: true,
		},
		isQueueTypeAll: {
			type: Boolean,
			required: true,
		},
	},
	computed:
	{
		dialog(): ?ImModelChat
		{
			return this.$store.getters['chats/get'](this.dialogId, true);
		},
		session(): ?ImolModelSession
		{
			return this.$store.getters['sessions/getByChatId'](this.dialog.chatId, true);
		},
		isNewSession(): boolean
		{
			if (!this.session)
			{
				return false;
			}

			return this.session.status === StatusGroup.new;
		},
		isOperator(): boolean
		{
			const userId = Core.getUserId();

			return userId === this.session.operatorId;
		},
		isClosed(): boolean
		{
			return this.session ? this.session.isClosed : false;
		},
	},
	template: `
		<div class="bx-imol-textarea_join-panel-container">
			<ChatControlPanel v-if="(isNewSession && isOperator) || isQueueTypeAll" :dialogId="dialogId" :isQueueTypeAll="isQueueTypeAll"/>
			<JoinPanel v-else :dialogId="dialogId" :isClosed="isClosed" :isNewSession="isNewSession"/>
		</div>
	`,
};
