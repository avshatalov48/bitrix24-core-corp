import { Core } from 'im.v2.application.core';
import { StatusGroup } from 'imopenlines.v2.const';
import { FinishService, PinService, InterceptService } from 'imopenlines.v2.provider.service';
import type { JsonObject } from 'main.core';

import { ChatHeader } from 'im.v2.component.content.elements';
import { ChatTransfer } from '../entity-selector/chat-transfer/chat-transfer';

import './css/header.css';

import type { ImModelChat } from 'im.v2.model';
import type { ImolModelSession } from 'imopenlines.v2.model';

// @vue/component
export const OpenLinesHeader = {
	name: 'OpenLinesHeader',
	components: { ChatHeader, ChatTransfer },
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
		dialog(): ImModelChat
		{
			return this.$store.getters['chats/get'](this.dialogId, true);
		},
		session(): ImolModelSession
		{
			return this.$store.getters['sessions/getByChatId'](this.dialog.chatId, true);
		},
		isPinned(): boolean
		{
			return this.session ? this.session.pinned : false;
		},
		isClosed(): boolean
		{
			return this.session ? this.session.isClosed : false;
		},
		isOwner(): boolean
		{
			const ownerId = this.dialog.ownerId;

			if (!ownerId)
			{
				return false;
			}

			const userId = Core.getUserId();

			return ownerId === userId;
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
		textForPinButton(): string
		{
			return this.isPinned
				? this.loc('IMOL_CONTENT_HEADER_BUTTON_UNPIN')
				: this.loc('IMOL_CONTENT_HEADER_BUTTON_PIN');
		},
		classIconButtonPin(): string
		{
			return this.isPinned ? 'fa-link-slash' : 'fa-link';
		},
	},
	methods:
	{
		onMarkSpam(): Promise
		{
			return this.getFinishService().markSpamChat(this.dialogId);
		},
		onFinish(): Promise
		{
			return this.getFinishService().finishChat(this.dialogId);
		},
		onPin(): Promise
		{
			if (this.isPinned)
			{
				return this.getPinService().unpinChat(this.dialogId);
			}

			return this.getPinService().pinChat(this.dialogId);
		},
		onIntercept(): Promise
		{
			return this.getInterceptService().interceptDialog(this.dialogId);
		},
		openChatTransferPopup()
		{
			this.showChatTransferPopup = true;
		},
		getFinishService(): FinishService
		{
			if (!this.finishService)
			{
				this.finishService = new FinishService();
			}

			return this.finishService;
		},
		getPinService(): PinService
		{
			if (!this.pinService)
			{
				this.pinService = new PinService();
			}

			return this.pinService;
		},
		getInterceptService(): InterceptService
		{
			if (!this.interceptService)
			{
				this.interceptService = new InterceptService();
			}

			return this.interceptService;
		},
		loc(phraseCode: string): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode);
		},
	},
	template: `
		<div class="bx-imol-header-button_container">
			<ChatHeader :dialogId="dialogId" :withCallButton="false" :withSearchButton="false">
				<template v-if="!isClosed" #before-actions>
					<ul v-if="isOperator || isNewSession" class="bx-imol-header-button_container-list">
						<li v-if="isOperator || isQueueTypeAll" class="bx-imol-header-button_container-item">
							<button
								:title="loc('IMOL_CONTENT_HEADER_BUTTON_SPAM')"
								class="bx-imol-header-button__icon-container"
								@click="onMarkSpam"
							>
								<i class="bx-imol-header-button__icon fa-solid fa-triangle-exclamation fa-lg"></i>
							</button>
						</li>
						<template v-if="isOwner">
							<li class="bx-imol-header-button_container-item">
								<button
									:title="loc('IMOL_CONTENT_HEADER_BUTTON_FINISH')"
									class="bx-imol-header-button__icon-container"
									@click="onFinish"
								>
									<i class="bx-imol-header-button__icon fa-regular fa-circle-check fa-lg"></i>
								</button>
							</li>
							<li class="bx-imol-header-button_container-item">
								<button
									:title="textForPinButton"
									class="bx-imol-header-button__icon-container"
									@click="onPin"
								>
									<i class="bx-imol-header-button__icon fa-solid fa-lg" :class="classIconButtonPin"></i>
								</button>
							</li>
							<li class="bx-imol-header-button_container-item">
								<button
									:title="loc('IMOL_CONTENT_BUTTON_TRANSFER')"
									:class="{'--active': showChatTransferPopup}"
									class="bx-imol-header-button__icon-container"
									@click="openChatTransferPopup"
									ref="transfer-chat"
								>
									<i class="bx-imol-header-button__icon fa-solid fa-arrows-turn-right fa-lg"></i>
								</button>
							</li>
						</template>
					</ul>
					<div v-else class="bx-imol-header-button_container-item">
						<button
							:title="loc('IMOL_CONTENT_HEADER_BUTTON_INTERCEPT')"
							class="bx-imol-header-button__icon-container"
							@click="onIntercept"
						>
							<i class="bx-imol-header-button__icon fa-solid fa-arrows-left-right fa-xl"></i>
						</button>
					</div>
				</template>
			</ChatHeader>
			<ChatTransfer
				:bindElement="$refs['transfer-chat'] || {}"
				:dialogId="dialogId"
				:showPopup="showChatTransferPopup"
				:popupConfig="{offsetTop: 15, offsetLeft: -300}"
				@close="showChatTransferPopup = false"
			/>
		</div>
	`,
};
