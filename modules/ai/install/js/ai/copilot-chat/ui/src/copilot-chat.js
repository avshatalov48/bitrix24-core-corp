import { Tag, Type, Loc, bind, unbind } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Popup, type PopupOptions, type MenuItemOptions } from 'main.popup';
import { type VueRefValue, BitrixVue, ref } from 'ui.vue3';
import { Status as CopilotChatStatus } from './components/copilot-chat-status';
import { CopilotChat as CopilotChatComponent, type CopilotChatHeaderProps, type CopilotChatSlots } from './components/copilot-chat.js';

import './css/copilot-chat-popup.css';
import type { CopilotChatMessage, CopilotChatMessageButton } from './types';

export type CopilotChatBotOptions = {
	avatar: string;
	messageTitle: string;
	messageMenuItems: MenuItemOptions[],
}

export const CopilotChatEvents = Object.freeze({
	ADD_USER_MESSAGE: 'addUserMessage',
	ADD_BOT_MESSAGE: 'addBotMessage',
	ADD_OLD_MESSAGES: 'addBotMessage',
	ADD_NEW_MESSAGES: 'addMessages',
	CLICK_ON_MESSAGE_BUTTON: 'clickOnMessageButton',
	VIEW_NEW_MESSAGE: 'viewMessage',
	SHOW_LOADER: 'showLoader',
	HIDE_LOADER: 'hideLoader',
	SHOW_COPILOT_WRITING_LOADER: 'showCopilotWritingLoader',
	HIDE_COPILOT_WRITING_LOADER: 'hideCopilotWritingLoader',
	DISABLE_INPUT_FIELD: 'disableInputField',
	ENABLE_INPUT_FIELD: 'enableInputField',
	SET_MESSAGE_STATUS: 'setMessageStatus',
	SHOW_ERROR_SCREEN: 'showErrorScreen',
	HIDE_ERROR_SCREEN: 'hideErrorScreen',
	MESSAGES_SCROLL_TOP: 'messagesListScrollTop',
});

export const CopilotChatMessageStatus = {
	DEPART: 'depart',
	SENT: 'sent',
	DELIVERED: 'delivered',
	ERROR: 'error',
};

export type CopilotChatOptions = {
	header: CopilotChatHeaderProps;
	botOptions: CopilotChatBotOptions;
	slots: CopilotChatSlots;
	vueComponents: Object;
	popupOptions: PopupOptions;
	useChatStatus: boolean;
	copilotMessageMenuItems: MenuItemOptions[];
	userMessageMenuItems: MenuItemOptions[];
	scrollToTheEndAfterFirstShow: boolean;
	showCopilotWarningMessage: boolean;
	userAvatar: string;
	inputPlaceholder?: string;
	loaderText?: string;
}

export class CopilotChat extends EventEmitter
{
	#copilotChatOptions: CopilotChatOptions;
	#popupOptions: PopupOptions;
	#popup: Popup;
	#app;
	#messages: VueRefValue<CopilotChatMessage[]>;
	#isShowLoader: VueRefValue<boolean>;
	#isInputDisabled: VueRefValue<boolean>;
	#chatStatus: VueRefValue<string>;
	#useChatStatus: VueRefValue<boolean>;
	#copilotMessageMenuItems: VueRefValue<MenuItemOptions[]>;
	#userMessageMenuItems: VueRefValue<MenuItemOptions[]>;
	#scrollToTheEndAfterFirstShow: VueRefValue<boolean>;
	#showCopilotWarningMessage: VueRefValue<boolean>;
	#copilotChatBotOptions: VueRefValue<CopilotChatBotOptions>;
	#userAvatar: VueRefValue<string>;
	#inputPlaceholder: string;
	#loaderText: ?string;

	constructor(options: CopilotChatOptions = {})
	{
		super(options);

		this.setEventNamespace('AI.CopilotChat');
		this.#copilotChatOptions = options || {};
		this.#copilotChatBotOptions = ref(options?.botOptions ?? {});
		this.#popupOptions = options?.popupOptions || {};

		this.#messages = ref([]);
		this.#copilotMessageMenuItems = ref(
			Type.isArray(options.botOptions.messageMenuItems) ? options.botOptions.messageMenuItems : [],
		);
		this.#userMessageMenuItems = ref(Type.isArray(options.userMessageMenuItems) ? options.userMessageMenuItems : []);
		this.#isShowLoader = ref(false);
		this.#isInputDisabled = ref(false);
		this.#chatStatus = ref(CopilotChatStatus.NONE);
		this.#useChatStatus = ref(Type.isBoolean(options.useChatStatus) ? options.useChatStatus : true);
		this.#scrollToTheEndAfterFirstShow = ref(
			Type.isBoolean(options.scrollToTheEndAfterFirstShow)
				? options.scrollToTheEndAfterFirstShow
				: true,
		);
		this.#showCopilotWarningMessage = ref(options.showCopilotWarningMessage === true);
		this.#userAvatar = ref(options.userAvatar ?? '');
		this.#inputPlaceholder = options.inputPlaceholder ?? Loc.getMessage('AI_COPILOT_CHAT_INPUT_PLACEHOLDER');
		this.#loaderText = options.loaderText;
	}

	static getDefaultMinWidth(): number
	{
		return 375;
	}

	static getDefaultHeight(): number
	{
		return 669;
	}

	isMessageInList(messageId: number): boolean
	{
		return this.#messages.value.findLast((message) => {
			return message.id === messageId;
		});
	}

	addUserMessage(message: CopilotChatMessage, emitEvent: boolean = true): void
	{
		const newUserMessage: CopilotChatMessage = {
			type: 'Default',
			...message,
			authorId: 1,
			status: CopilotChatMessageStatus.DEPART,
		};

		this.#addNewMessage(newUserMessage, emitEvent);
	}

	addBotMessage(message: CopilotChatMessage, emitEvent: boolean = true): void
	{
		const newUserMessage: CopilotChatMessage = {
			type: 'Default',
			...message,
			authorId: 0,
			status: null,
		};

		this.#addNewMessage(newUserMessage, emitEvent);
	}

	addSystemMessage(message: CopilotChatMessage, emitEvent: boolean = true): void
	{
		const newSystemMessage: CopilotChatMessage = {
			...message,
			authorId: null,
			status: null,
		};

		this.#addNewMessage(newSystemMessage, emitEvent);
	}

	enableInput(): void
	{
		this.#isInputDisabled.value = false;

		this.emit(CopilotChatEvents.ENABLE_INPUT_FIELD);
	}

	disableInput(): void
	{
		this.#isInputDisabled.value = true;

		this.emit(CopilotChatEvents.DISABLE_INPUT_FIELD);
	}

	showLoader(): void
	{
		this.#isShowLoader.value = true;

		this.emit(CopilotChatEvents.SHOW_LOADER);
	}

	hideLoader(): void
	{
		this.#isShowLoader.value = false;

		this.emit(CopilotChatEvents.HIDE_LOADER);
	}

	setMessageStatusDepart(messageId: string): void
	{
		this.#setMessageStatus(messageId, CopilotChatMessageStatus.DEPART);
	}

	setMessageStatusDelivered(messageId: string): void
	{
		this.#setMessageStatus(messageId, CopilotChatMessageStatus.DELIVERED);
	}

	setMessageStatusSent(messageId: string): void
	{
		this.#setMessageStatus(messageId, CopilotChatMessageStatus.SENT);
	}

	setCopilotWritingStatus(value: boolean): void
	{
		if (value === true)
		{
			this.#chatStatus.value = CopilotChatStatus.COPILOT_WRITING;
		}
		else
		{
			this.#chatStatus.value = CopilotChatStatus.NONE;
		}
	}

	setNewMessageIsViewed(messageId: number): void
	{
		this.emit(CopilotChatEvents.VIEW_NEW_MESSAGE, new BaseEvent({
			data: {
				id: messageId,
			},
		}));
	}

	setMessageId(messageId: number, newMessageId: number): void
	{
		const message: ?CopilotChatMessage = this.#messages.value.find((currentMessage) => currentMessage.id === messageId);

		if (!message)
		{
			return;
		}

		message.id = newMessageId;
	}

	setMessageDate(messageId: number, date: string): void
	{
		const message: ?CopilotChatMessage = this.#messages.value.find((currentMessage) => currentMessage.id === messageId);

		if (!message)
		{
			return;
		}

		message.dateCreated = date;
	}

	emitClickOnMessageButton(data: { buttonId: number, messageId: number }): void
	{
		const { buttonId, messageId } = data;

		const clickedMessageButton = this.#findMessageButton(messageId, buttonId);

		clickedMessageButton.isSelected = true;

		this.emit(CopilotChatEvents.CLICK_ON_MESSAGE_BUTTON, {
			messageId,
			button: { ...clickedMessageButton },
		});
	}

	#findMessageButton(messageId: number, buttonId: number): ?CopilotChatMessageButton
	{
		const searchedMessage = this.#messages.value.find((message) => message.id === messageId);

		if (Type.isArray(searchedMessage.params?.buttons) === false)
		{
			return null;
		}

		return searchedMessage.params.buttons.find((button) => button.id === buttonId) ?? null;
	}

	#setMessageStatus(messageId: number, status: string): void
	{
		const message: ?CopilotChatMessage = this.#messages.value.find((currentMessage) => currentMessage.id === messageId);

		if (!message)
		{
			return;
		}

		message.status = status;

		this.emit(CopilotChatEvents.SET_MESSAGE_STATUS, {
			messageId,
			status,
		});
	}

	#addNewMessage(message: CopilotChatMessage, emitEvent: boolean = true): void
	{
		const newMessageId = Math.round(-Math.random() * 1000);

		const isCopilotChatShown = this.isShown();

		const newMessage: CopilotChatMessage = {
			id: newMessageId,
			dateCreated: (new Date()).toISOString(),
			status: CopilotChatMessageStatus.DEPART,
			authorId: 0,
			...message,
			viewed: message?.viewed ?? isCopilotChatShown,
		};

		this.#messages.value.push(newMessage);

		if (emitEvent === false)
		{
			return;
		}

		if (newMessage.authorId === 0)
		{
			this.emit(CopilotChatEvents.ADD_BOT_MESSAGE, {
				message: newMessage,
			});
		}
		else
		{
			this.emit(CopilotChatEvents.ADD_USER_MESSAGE, {
				message: newMessage,
			});
		}
	}

	show(): void
	{
		if (!this.#popup)
		{
			this.#initPopup();
		}

		this.#popup.show();
	}

	hide(): void
	{
		this.#popup?.close();
	}

	isShown(): boolean
	{
		return Boolean(this.#popup?.isShown());
	}

	adjustPosition(): void
	{
		this.#popup?.adjustPosition({
			forceBindPosition: true,
		});
	}

	setUserAvatar(avatar: string): void
	{
		this.#userAvatar.value = avatar;
	}

	#initPopup(): Popup
	{
		const adjustPopupPosition = this.adjustPosition.bind(this);
		bind(window, 'resize', adjustPopupPosition);

		this.#popup = new Popup({
			...this.#popupOptions,
			content: this.#renderPopupContent(),
			minWidth: this.#popupOptions?.minWidth ?? CopilotChat.getDefaultMinWidth(),
			height: this.#popupOptions?.height ?? CopilotChat.getDefaultHeight(),
			contentNoPaddings: true,
			padding: 0,
			borderRadius: '16px',
			className: `ai__copilot-chat-popup ${this.#popupOptions.className ?? ''}`,
			cacheable: this.#popupOptions?.cacheable ?? false,
			events: {
				onPopupAfterClose: () => {
					this.#popup = null;
					this.#app.unmount();

					if (Type.isFunction(this.#popupOptions?.events?.onPopupAfterClose))
					{
						this.#popupOptions?.events?.onPopupAfterClose();
					}

					unbind(window, 'resize', adjustPopupPosition);
				},
			},
		});

		return this.#popup;
	}

	#renderPopupContent(): HTMLElement
	{
		const appContainer = Tag.render`<div class="ai__copilot-chat-popup-content"></div>`;
		this.#app = BitrixVue.createApp({
			name: 'CopilotChatPopup',
			components: {
				CopilotChat: CopilotChatComponent,
				...(this.#copilotChatOptions?.vueComponents),
			},
			template: `
				<CopilotChat>
					<template v-slot:loader>
						${this.#copilotChatOptions.slots?.LOADER ?? ''}
					</template>
					<template v-slot:loaderError>
						${this.#copilotChatOptions.slots?.LOADER_ERROR ?? ''}
					</template>
				</CopilotChat>
			`,
		}, {
			...this.#copilotChatOptions,
			messages: this.#messages.value,
			copilotChatInstance: this,
			showLoader: this.#isShowLoader,
			disableInput: this.#isInputDisabled,
			status: this.#chatStatus,
			useStatus: this.#useChatStatus,
			scrollToTheEndAfterFirstShow: this.#scrollToTheEndAfterFirstShow,
			copilotMessageMenuItems: this.#copilotMessageMenuItems.value,
			userMessageMenuItems: this.#userMessageMenuItems.value,
			isShowWarningMessage: this.#showCopilotWarningMessage,
			botOptions: this.#copilotChatBotOptions.value,
			userAvatar: this.#userAvatar,
			inputPlaceholder: this.#inputPlaceholder,
			loaderText: this.#loaderText,
		});

		this.#app.mount(appContainer);

		return appContainer;
	}
}
