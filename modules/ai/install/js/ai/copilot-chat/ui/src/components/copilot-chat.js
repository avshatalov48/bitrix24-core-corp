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
	},
	data(): CopilotChatData {
		return {
			isCopilotWriting: false,
			isShowErrorScreen: false,
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
			if (event.target.scrollTop < 100)
			{
				this.instance.emit(CopilotChatEvents.MESSAGES_SCROLL_TOP);
			}
		});
	},
	beforeUnmount() {
		this.instance.unsubscribe(CopilotChatEvents.ADD_USER_MESSAGE, this.handleAddNewUserMessage);
	},
	watch: {
		'messagesList.length': function(newMessagesCount, oldMessagesCount) {
			if (newMessagesCount - oldMessagesCount === 1)
			{
				requestAnimationFrame(() => {
					this.scrollMessagesListToTheEnd(true);
				});
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
			</header>
			<main ref="main" class="ai__copilot-chat_main">
				<div
					v-if="isLoaderShown"
					class="ai__copilot-chat_main-loader-container"
				>
					<slot name="loader">
						<CopilotChatHistoryLoader :text="loaderText" />
					</slot>
				</div>
				<div v-else-if="isShowErrorScreen">
					<slot name="loaderError">
						Sorry, we can't load messages, try later
					</slot>
				</div>
				<CopilotChatMessages
					v-else
					@clickMessageButton="handleClickOnMessageButton"
					:user-avatar="userPhoto"
					:copilot-avatar="botData.avatar"
					:messages="messagesList"
					:welcome-message-html-element="welcomeMessageHtml"
					:copilot-message-title="botData.messageTitle"
					:copilot-message-menu-items="botData.messageMenuItems"
					:user-message-menu-items="userMessageMenuItems"
				></CopilotChatMessages>
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
