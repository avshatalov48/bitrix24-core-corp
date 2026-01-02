import { bind } from 'main.core';
import type { MenuItemOptions } from 'main.popup';
import { CopilotChatEvents } from '../copilot-chat';

import type { CopilotChatBotOptions, CopilotChat as CopilotChatInstance } from '../copilot-chat';
import { NewMessagesVisibilityObserver, NewMessagesVisibilityObserverEvents } from '../helpers/new-messages-visibility-observer';
import { CopilotChatHeader } from './copilot-chat-header';
import { CopilotChatInput } from './copilot-chat-input';
import type { CopilotChatMessage as CopilotChatMessageData } from '../types';
import { CopilotChatMessages } from './copilot-chat-messages';
import { CopilotChatHistoryLoader } from './copilot-chat-history-loader';
import { containerClassname as newMessagesLabelContainerClassname } from './copilot-chat-new-messages-label';
import { CopilotChatStatus } from './copilot-chat-status';
import { CopilotChatWarningMessage } from './copilot-chat-warning-message';
import { CopilotChatLoadingBar } from './copilot-chat-loading-bar';
import { CopilotChatLoadHistoryError } from './copilot-chat-load-history-error';

import '../css/copilot-chat.css';
export type CopilotChatHeaderProps = {
	title: string;
	subtitle: string;
	avatar: string;
	useCloseIcon?: boolean;
	menu: ?CopilotChatMenuOptions;
	extraParams?: Object;
}

export type CopilotChatMenuOptions = {
	items: MenuItemOptions[];
}

type CopilotChatData = {
	messages: CopilotChatMessageData;
}

export type CopilotChatSlots = {
	LOADER?: string;
	LOADER_ERROR?: string;
};

export const CopilotChatSlot = {
	LOADER: 'loader',
};

export const CopilotChat = {
	name: 'CopilotChat',
	components: {
		CopilotChatHeader,
		CopilotChatMessages,
		CopilotChatInput,
		CopilotChatHistoryLoader,
		CopilotChatStatus,
		CopilotChatWarningMessage,
		CopilotChatLoadingBar,
		CopilotChatLoadHistoryError,
	},
	props: {
		header: Object,
		welcomeMessageHtml: HTMLElement,
		botOptions: Object,
		scrollToTheEndAfterFirstShow: Object,
		slots: Object,
		isShowWarningMessage: {
			type: Object,
			required: false,
			default: () => ({ value: false }),
		},
		messages: Array,
		copilotChatInstance: Object,
		useInput: {
			type: Boolean,
			required: false,
			default: true,
		},
		disableInput: Object,
		showLoader: Object,
		isOldMessagesLoading: Object,
		showCopilotWritingStatus: Object,
		status: Object,
		useStatus: Object,
		copilotMessageMenuItems: {
			type: Array,
			required: false,
			default: () => ([]),
		},
		userAvatar: {
			type: Object,
			required: false,
		},
		userMessageMenuItems: {
			type: Array,
			required: false,
			default: () => ([]),
		},
		inputPlaceholder: {
			type: String,
			required: false,
			default: '',
		},
		loaderText: {
			type: String,
			required: false,
		},
		isShowLoadHistoryError: {
			type: Object,
		},
	},
	data(): CopilotChatData {
		return {
			isCopilotWriting: false,
			scrollPosition: 0,
			scrollHeight: 0,
		};
	},
	provide(): { instance: CopilotChatInstance} {
		return {
			instance: this.copilotChatInstance,
			observer: this.observer,
		};
	},
	computed: {
		userPhoto(): string {
			return this.userAvatar?.value || '/bitrix/js/ui/icons/b24/images/ui-user.svg?v2';
		},
		isInputDisabled(): boolean {
			return this.disableInput.value === true;
		},
		isLoaderShown(): boolean {
			return this.showLoader.value === true;
		},
		isLoadingOldMessages(): boolean {
			return this.isOldMessagesLoading?.value === true;
		},
		isWarningMessageShown(): boolean {
			return this.isShowWarningMessage?.value === true;
		},
		instance(): CopilotChatInstance {
			return this.copilotChatInstance;
		},
		Slot(): CopilotChatSlots {
			return {
				...this.slots,
			};
		},
		headerProps(): CopilotChatHeaderProps {
			return {
				title: this.header?.title,
				subtitle: this.header?.subtitle,
				avatar: this.header?.avatar,
				useCloseIcon: this.header?.useCloseIcon,
				menu: this.header?.menu,
			};
		},
		botData(): CopilotChatBotOptions {
			return {
				messageTitle: this.botOptions.messageTitle,
				avatar: this.botOptions.avatar,
				messageMenuItems: this.botOptions?.messageMenuItems ?? [],
			};
		},
		messagesList(): CopilotChatMessageData[] {
			return this.messages;
		},
		haveNewMessages(): boolean {
			return this.messagesList.some((message) => message.viewed === false);
		},
		copilotChatStatus(): string {
			return this.status.value;
		},
		isChatStatusUsed(): boolean {
			return this.useStatus.value;
		},
		isScrollToTheEndAfterMounted(): boolean {
			return this.scrollToTheEndAfterFirstShow.value;
		},
	},
	methods: {
		hideChat(): void {
			this.copilotChatInstance.hide();
		},
		async handleSubmitMessage(userMessage: string): void {
			const newMessage: CopilotChatMessageData = {
				content: userMessage,
			};

			this.instance.addUserMessage(newMessage);
		},
		scrollMessagesListAfterOpen(): void {
			if (this.haveNewMessages)
			{
				const newMessagesLabel: HTMLElement = this.$refs.main.querySelector(`.${newMessagesLabelContainerClassname}`);

				newMessagesLabel?.scrollIntoView();
			}
			else
			{
				this.scrollMessagesListToTheEnd();
			}
		},
		scrollMessagesListToTheEnd(isSmooth: boolean = false): void {
			this.$refs.main.scrollTo({
				left: 0,
				top: 9999,
				behavior: isSmooth ? 'smooth' : 'auto',
			});
		},
		handleClickOnMessageButton(eventData): void {
			this.instance.emitClickOnMessageButton({
				messageId: eventData.messageId,
				buttonId: eventData.buttonId,
			});
		},
		handleAddNewUserMessage(): void {
			requestAnimationFrame(() => {
				this.scrollMessagesListToTheEnd(true);
			});
		},
		restoreScrollPosition(): void {
			this.scrollPosition = this.$refs.main.scrollTop;
			this.scrollHeight = this.$refs.main.scrollHeight;

			requestAnimationFrame(() => {
				this.$refs.main.scrollTop = this.$refs.main.scrollHeight - this.scrollHeight + this.scrollPosition;
			});
		},
		handleRetryMessage(messageId: number): void {
			this.instance.emit(CopilotChatEvents.RETRY_SEND_MESSAGE, {
				messageId,
			});
		},
		handleRemoveMessage(messageId: number): void {
			this.instance.emit(CopilotChatEvents.REMOVE_MESSAGE, {
				messageId,
			});
		},
		handleRetryLoadHistoryButtonClick()
		{
			this.instance.emitRetryLoadHistory();
		},
	},
	beforeCreate() {
		this.observer = new NewMessagesVisibilityObserver();

		this.observer.subscribe(NewMessagesVisibilityObserverEvents.VIEW_NEW_MESSAGE, (event) => {
			this.instance.setNewMessageIsViewed(event.getData().id);
		});
	},
	mounted() {
		this.observer.setRoot(this.$refs.main);
		this.observer.init();

		this.instance.subscribe(CopilotChatEvents.ADD_USER_MESSAGE, this.handleAddNewUserMessage);

		requestAnimationFrame(() => {
			if (this.isScrollToTheEndAfterMounted)
			{
				this.scrollMessagesListAfterOpen();
			}
		});

		bind(this.$refs.main, 'scroll', (event: Event) => {
			const scrollElement: HTMLElement = event.target;

			if ((scrollElement.scrollTop / scrollElement.scrollHeight) < 0.05)
			{
				this.instance.emit(CopilotChatEvents.MESSAGES_SCROLL_TOP);
			}
		});
	},
	beforeUnmount() {
		this.instance.unsubscribe(CopilotChatEvents.ADD_USER_MESSAGE, this.handleAddNewUserMessage);
	},
	watch: {
		isLoaderShown(newValue: boolean, oldValue: boolean) {
			if (newValue === false && oldValue === true)
			{
				requestAnimationFrame(() => {
					this.scrollMessagesListToTheEnd();
				});
			}
		},
		'messagesList.length': function(newMessagesCount, oldMessagesCount) {
			if (newMessagesCount - oldMessagesCount === 1)
			{
				requestAnimationFrame(() => {
					this.scrollMessagesListToTheEnd(true);
				});
			}

			if (oldMessagesCount > 1 && newMessagesCount > 1)
			{
				this.restoreScrollPosition();
			}

			requestAnimationFrame(() => {
				if (oldMessagesCount === 0 && newMessagesCount > 1 && this.isScrollToTheEndAfterMounted)
				{
					this.scrollMessagesListToTheEnd();
				}
			});
		},
	},
	template: `
		<div class="ai__copilot-chat">
			<header class="ai__copilot-chat_header">
				<CopilotChatHeader
					:title="headerProps.title"
					:subtitle="headerProps.subtitle"
					:avatar="headerProps.avatar"
					:use-close-icon="headerProps.useCloseIcon"
					:menu="headerProps.menu"
					@clickOnCloseIcon="hideChat"
				/>
				<div class="ai__copilot-chat_loading-bar" v-if="isLoadingOldMessages">
					<CopilotChatLoadingBar />
				</div>
			</header>
			<main ref="main" class="ai__copilot-chat_main">
				<TransitionGroup name="main">
				<div
					v-if="isLoaderShown"
					class="ai__copilot-chat_main-loader-container"
				>
					<slot name="loader">
						<CopilotChatHistoryLoader :text="loaderText" />
					</slot>
				</div>
				<div
					v-else-if="isShowLoadHistoryError.value"
					class="ai__copilot-chat_load-history-error"
				>
					<slot name="loaderError">
						<CopilotChatLoadHistoryError @retryButtonClick="handleRetryLoadHistoryButtonClick" />
					</slot>
				</div>
					<CopilotChatMessages
						v-else
						@clickMessageButton="handleClickOnMessageButton"
						@retry="handleRetryMessage"
						@remove="handleRemoveMessage"
						:user-avatar="userPhoto"
						:copilot-avatar="botData.avatar"
						:messages="messagesList"
						:welcome-message-html-element="welcomeMessageHtml"
						:copilot-message-title="botData.messageTitle"
						:copilot-message-menu-items="botData.messageMenuItems"
						:user-message-menu-items="userMessageMenuItems"
					></CopilotChatMessages>
				</TransitionGroup>
				<CopilotChatStatus
					v-if="isLoaderShown === false && isChatStatusUsed"
					:status="copilotChatStatus"
				/>
				<div id="anchor"></div>
			</main>
			<footer class="ai__copilot-chat_footer">
				<CopilotChatInput
					v-if="useInput"
					:disabled="isInputDisabled"
					:placeholder="inputPlaceholder"
					@submit="handleSubmitMessage"
				/>
				<div
					v-if="isWarningMessageShown"
					class="ai__copilot-chat_warning-message"
				>
					<CopilotChatWarningMessage />
				</div>
			</footer>
		</div>
	`,
};
