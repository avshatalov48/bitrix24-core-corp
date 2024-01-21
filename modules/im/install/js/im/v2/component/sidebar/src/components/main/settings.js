import { hint } from 'ui.vue3.directives.hint';

import { Core } from 'im.v2.application.core';
import { Toggle, ToggleSize } from 'im.v2.component.elements';
import { ImModelChat } from 'im.v2.model';
import { ChatActionType } from 'im.v2.const';
import { ChatService } from 'im.v2.provider.service';
import { PermissionManager } from 'im.v2.lib.permission';

import '../../css/main/settings.css';

// @vue/component
export const Settings = {
	name: 'MainPreviewSettings',
	directives: { hint },
	components: { Toggle },
	props:
	{
		isLoading: {
			type: Boolean,
			default: false,
		},
		dialogId: {
			type: String,
			required: true,
		},
		isModerator: {
			type: Boolean,
			default: false,
		},
	},
	data() {
		return {
			autoDeleteEnabled: false,
		};
	},
	computed:
	{
		ToggleSize: () => ToggleSize,
		dialog(): ImModelChat
		{
			return this.$store.getters['chats/get'](this.dialogId, true);
		},
		isGroupChat(): boolean
		{
			return this.dialogId.startsWith('chat');
		},
		canBeMuted(): boolean
		{
			return PermissionManager.getInstance().canPerformAction(ChatActionType.mute, this.dialogId);
		},
		isChatMuted(): boolean
		{
			const isMuted = this.dialog.muteList.find((element) => {
				return element === Core.getUserId();
			});

			return Boolean(isMuted);
		},
		hintMuteNotAvailable(): ?Object
		{
			if (this.canBeMuted)
			{
				return null;
			}

			return {
				text: this.$Bitrix.Loc.getMessage('IM_SIDEBAR_MUTE_NOT_AVAILABLE'),
				popupOptions: {
					angle: true,
					targetContainer: document.body,
					offsetLeft: 141,
					offsetTop: -10,
					bindOptions: {
						position: 'top',
					},
				},
			};
		},
		hintAutoDeleteNotAvailable()
		{
			return {
				text: this.$Bitrix.Loc.getMessage('IM_MESSENGER_NOT_AVAILABLE'),
				popupOptions: {
					bindOptions: {
						position: 'top',
					},
					angle: true,
					targetContainer: document.body,
					offsetLeft: 125,
					offsetTop: -10,
				},
			};
		},
		chatTypeClass(): string
		{
			return this.isGroupChat ? '--group-chat' : '--personal';
		},
	},
	methods:
	{
		getChatService(): ChatService
		{
			if (!this.chatService)
			{
				this.chatService = new ChatService();
			}

			return this.chatService;
		},
		muteActionHandler()
		{
			if (!this.canBeMuted)
			{
				return;
			}

			if (this.isChatMuted)
			{
				this.getChatService().unmuteChat(this.dialogId);
			}
			else
			{
				this.getChatService().muteChat(this.dialogId);
			}
		},
	},
	template: `
		<div v-if="isLoading" class="bx-im-sidebar-main-settings__skeleton" :class="chatTypeClass"></div>
		<div v-else class="bx-im-sidebar-main-settings__container bx-im-sidebar-main-settings__scope" :class="chatTypeClass">
			<div
				v-if="isGroupChat"
				class="bx-im-sidebar-main-settings__notification-container"
				:class="[canBeMuted ? '' : '--not-active']"
				v-hint="hintMuteNotAvailable"
			>
				<div class="bx-im-sidebar-main-settings__notification-title">
					<div class="bx-im-sidebar-main-settings__title-text bx-im-sidebar-main-settings__title-icon --notification">
						{{ $Bitrix.Loc.getMessage('IM_SIDEBAR_ENABLE_NOTIFICATION_TITLE_2') }}
					</div>
					<Toggle :size="ToggleSize.M" :isEnabled="!isChatMuted" @change="muteActionHandler" />
				</div>
			</div>
			<div class="bx-im-sidebar-main-settings__autodelete-container --not-active" v-hint="hintAutoDeleteNotAvailable">
				<div class="bx-im-sidebar-main-settings__autodelete-title">
					<div class="bx-im-sidebar-main-settings__title-text bx-im-sidebar-main-settings__title-icon --autodelete">
						{{ $Bitrix.Loc.getMessage('IM_SIDEBAR_ENABLE_AUTODELETE_TITLE') }}
					</div>
					<Toggle :size="ToggleSize.M" :isEnabled="autoDeleteEnabled" />
				</div>
				<div class="bx-im-sidebar-main-settings__autodelete-status">
					{{ $Bitrix.Loc.getMessage('IM_SIDEBAR_AUTODELETE_STATUS_OFF') }}
				</div>
			</div>
		</div>
	`,
};
