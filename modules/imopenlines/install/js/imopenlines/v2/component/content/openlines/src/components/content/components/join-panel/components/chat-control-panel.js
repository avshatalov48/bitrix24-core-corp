import { Button as ButtonPanel, ButtonColor, ButtonSize } from 'im.v2.component.elements';
import { AnswerService, SkipService } from 'imopenlines.v2.provider.service';

import { ChatTransfer } from '../../entity-selector/chat-transfer/chat-transfer';

import type { JsonObject } from 'main.core';
import type { CustomColorScheme } from 'im.v2.component.elements';

const BUTTON_COLOR = '#eef0f2';
const BUTTON_COLOR_TEXT = '#535658';
const BUTTON_COLOR_HOVER = '#dfe0e3';

// @vue/component
export const ChatControlPanel = {
	name: 'ChatControlPanel',
	components: { ButtonPanel, ChatTransfer },
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
	data(): JsonObject
	{
		return {
			showChatTransferPopup: false,
		};
	},
	computed:
	{
		ButtonSize: () => ButtonSize,
		ButtonColor: () => ButtonColor,
		buttonColorScheme(): CustomColorScheme
		{
			return {
				backgroundColor: BUTTON_COLOR,
				borderColor: 'transparent',
				iconColor: BUTTON_COLOR,
				textColor: BUTTON_COLOR_TEXT,
				hoverColor: BUTTON_COLOR_HOVER,
			};
		},
	},
	methods:
	{
		replyDialog(): Promise
		{
			return this.getAnswerService().requestAnswer(this.dialogId);
		},
		skipDialog(): Promise
		{
			return this.getSkipService().requestSkip(this.dialogId);
		},
		getAnswerService(): AnswerService
		{
			if (!this.answerService)
			{
				this.answerService = new AnswerService();
			}

			return this.answerService;
		},
		getSkipService(): SkipService
		{
			if (!this.skipService)
			{
				this.skipService = new SkipService();
			}

			return this.skipService;
		},
		openChatTransferPopup()
		{
			this.showChatTransferPopup = true;
		},
		loc(phraseCode: string): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode);
		},
	},
	template: `
		<ul class="bx-imol-textarea_join-panel-list-button">
			<li class="bx-imol-textarea_join-panel-item-button">
				<ButtonPanel
					:size="ButtonSize.L"
					:color="ButtonColor.Success"
					:text="loc('IMOL_CONTENT_TEXTAREA_JOIN_PANEL_ANSWER')"
					@click="replyDialog"
				/>
			</li>
			<li v-if="!isQueueTypeAll" class="bx-imol-textarea_join-panel-item-button">
				<ButtonPanel
					:size="ButtonSize.L"
					:color="ButtonColor.Danger"
					:text="loc('IMOL_CONTENT_TEXTAREA_JOIN_PANEL_SKIP')"
					@click="skipDialog"
				/>
			</li>
			<li class="bx-imol-textarea_join-panel-item-button" ref="transfer-chat">
				<ButtonPanel
					:size="ButtonSize.L"
					:customColorScheme="buttonColorScheme"
					:text="loc('IMOL_CONTENT_BUTTON_TRANSFER')"
					@click="openChatTransferPopup"
				/>
			</li>
		</ul>
		<ChatTransfer
			:bindElement="$refs['transfer-chat'] || {}"
			:dialogId="dialogId"
			:showPopup="showChatTransferPopup"
			:popupConfig="{offsetTop: -700, offsetLeft: 0}"
			@close="showChatTransferPopup = false"
		/>

	`,
};
