import { BaseEvent, EventEmitter } from 'main.core.events';
import { Loc } from 'main.core';

import { Messenger } from 'im.public';
import { LocalStorageKey, ChatType } from 'im.v2.const';
import { CallManager } from 'im.v2.lib.call';
import { LocalStorageManager } from 'im.v2.lib.local-storage';
import { PromoManager } from 'im.v2.lib.promo';
import { Analytics } from 'call.lib.analytics';
import { Util, CallAI } from 'call.core';

import { CallMenu } from './classes/call-menu';
import { CallTypes } from 'call.const';
import { CallButtonTitle } from './components/call-button-title';
import { CallButtonPromo } from './components/call-button-promo';
import { PulseAnimation } from './components/pulse-animation/pulse-animation';

import { hint } from 'ui.vue3.directives.hint';

import './css/call-button.css';

import type { JsonObject } from 'main.core';

// @vue/component
export const CallButton = {
	directives: { hint },
	components: { CallButtonTitle, CallButtonPromo, PulseAnimation },
	props:
	{
		dialog: {
			type: Object,
			required: true,
		},
		compactMode: {
			type: Boolean,
			default: false,
		},
	},
	data(): JsonObject
	{
		return {
			lastCallType: '',
			copilotMinUserLimit: CallAI.recordingMinUsers,
			isCopilotActive: CallAI.serviceEnabled,
			isTariffAvailable: CallAI.tariffAvailable,
			showPromo: false,
			showPromoTimer: null,
			promoId: 'call:copilot-call-button:29102024:all',
		};
	},
	computed:
	{
		dialogId(): string
		{
			return this.dialog.dialogId;
		},
		chatId(): number
		{
			return this.dialog.chatId;
		},
		userCount(): number
		{
			return this.dialog.userCounter;
		},
		isConference(): boolean
		{
			return this.dialog.type === ChatType.videoconf;
		},
		callButtonText(): string
		{
			const locCode = CallTypes[this.lastCallType].locCode;

			return this.loc(locCode);
		},
		hasActiveCurrentCall(): boolean
		{
			return CallManager.getInstance().hasActiveCurrentCall(this.dialogId);
		},
		hasActiveAnotherCall(): boolean
		{
			return CallManager.getInstance().hasActiveAnotherCall(this.dialogId);
		},
		isActive(): boolean
		{
			if (this.hasActiveCurrentCall)
			{
				return true;
			}

			if (this.hasActiveAnotherCall)
			{
				return false;
			}

			return CallManager.getInstance().chatCanBeCalled(this.dialogId);
		},
		userLimit(): number
		{
			return CallManager.getInstance().getCallUserLimit();
		},
		isChatUserLimitExceeded(): boolean
		{
			return CallManager.getInstance().isChatUserLimitExceeded(this.dialogId);
		},
		shouldShowMenu(): boolean
		{
			return this.isActive;
		},
		hintContent(): Object | null
		{
			if (!this.isChatUserLimitExceeded)
			{
				return null;
			}

			return {
				text: this.loc('IM_LIB_CALL_USER_LIMIT_EXCEEDED_TOOLTIP', { '#USER_LIMIT#': this.userLimit }),
				popupOptions: {
					bindOptions: {
						position: 'bottom',
					},
					angle: { position: 'top' },
					targetContainer: document.body,
					offsetLeft: 63,
					offsetTop: 0,
				},
			};
		},
		isCopilotCall(): boolean
		{
			return (
				this.isCopilotActive
				&& this.userCount >= this.copilotMinUserLimit
				&& !this.isConference
				&& this.isTariffAvailable
			);
		},
		callButtonContainerClasses(): Array<String>
		{
			return [
				'bx-call-chat-header-call-button__scope',
				'bx-call-chat-header-call-button__container',
				...(this.isConference ? ['--conference'] : []),
				...(this.isCopilotCall ? ['--copilot'] : []),
				...(!this.isActive ? ['--disabled'] : []),
			];
		},
		canShowPromo(): boolean
		{
			return (
				this.isCopilotCall
				&& PromoManager.getInstance().needToShow(this.promoId)
				&& !this.hasActiveCurrentCall
				&& this.isActive
			);
		},
	},
	created()
	{
		this.lastCallType = this.getLastCallChoice();
		this.subscribeToMenuItemClick();
		EventEmitter.subscribe('BX.Call.View:onShow', this.onShowCallView);
	},
	mounted()
	{
		this.showPromoTimer = setTimeout(() => {
			this.showPromo = this.canShowPromo;
		}, 20000);
	},
	beforeUnmount()
	{
		this.clearShowPromoTimer();
		this.showPromo = false;
		EventEmitter.unsubscribe('BX.Call.View:onShow', this.onShowCallView);
	},
	methods:
	{
		startVideoCall()
		{
			if (!this.isActive)
			{
				return;
			}

			Messenger.startVideoCall(this.dialogId);
		},
		subscribeToMenuItemClick()
		{
			this.getCallMenu().subscribe(
				CallMenu.events.onMenuItemClick,
				(event: BaseEvent<{id: string}>) => {
					const { id: callTypeId } = event.getData();
					this.saveLastCallChoice(callTypeId);
				},
			);
		},
		onShowCallView()
		{
			this.onClosePromo();
		},
		onButtonClick()
		{
			if (!this.isActive)
			{
				return;
			}

			if (this.isCopilotCall)
			{
				this.onClosePromo();
			}

			Analytics.getInstance().onChatHeaderStartCallClick({
				dialog: this.dialog,
				callType: this.lastCallType,
			});

			CallTypes[this.lastCallType].start(this.dialogId);
		},
		onMenuClick()
		{
			if (!this.shouldShowMenu)
			{
				return;
			}
			this.getCallMenu().openMenu(this.dialog, this.$refs.menu);
		},
		onStartConferenceClick()
		{
			if (!this.isActive)
			{
				return;
			}

			if (this.isCopilotCall)
			{
				this.onClosePromo();
			}

			Analytics.getInstance().onStartConferenceClick({ chatId: this.chatId });

			Messenger.openConference({ code: this.dialog.public.code });
		},
		getLastCallChoice(): string
		{
			return LocalStorageManager.getInstance().get(LocalStorageKey.lastCallType, CallTypes.video.id);
		},
		saveLastCallChoice(callTypeId: string)
		{
			this.lastCallType = callTypeId;
			LocalStorageManager.getInstance().set(LocalStorageKey.lastCallType, callTypeId);
		},
		getCallMenu(): CallMenu
		{
			if (!this.callMenu)
			{
				this.callMenu = new CallMenu();
			}

			return this.callMenu;
		},
		loc(phraseCode: string, replacements: {[p: string]: string} = {}): string
		{
			return Loc.getMessage(phraseCode, replacements);
		},
		onClosePromo()
		{
			this.clearShowPromoTimer();
			this.showPromo = false;
			PromoManager.getInstance().markAsWatched(this.promoId);
		},
		clearShowPromoTimer()
		{
			clearTimeout(this.showPromoTimer);
			this.showPromoTimer = null;
		},
	},
	template: `
		<PulseAnimation :showPulse="showPromo" :isConference="isConference">
			<div
				v-if="isConference"
				:class="callButtonContainerClasses"
				@click="onStartConferenceClick"
				ref="call-button"
			>
				<CallButtonTitle :compactMode="compactMode" :copilotMode="isCopilotCall" :text="loc('IM_CONTENT_CHAT_HEADER_START_CONFERENCE')" />
			</div>
			<div
				v-else
				:class="callButtonContainerClasses"
				v-hint="hintContent"
				@click="onButtonClick"
				ref="call-button"
			>
				<CallButtonTitle :compactMode="compactMode" :copilotMode="isCopilotCall" :text="callButtonText" />
				<div class="bx-call-chat-header-call-button__separator"></div>
				<div class="bx-call-chat-header-call-button__chevron_container" @click.stop="onMenuClick">
					<div class="bx-call-chat-header-call-button__chevron" ref="menu"></div>
				</div>
			</div>
			<CallButtonPromo
				v-if="showPromo"
				:bindElement="$refs['call-button']"
				@close="onClosePromo"
			/>
		</PulseAnimation>
	`,
};
