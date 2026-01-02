import { Type } from 'main.core';
import { EventEmitter } from 'main.core.events';
import {
	CopilotChat as CopilotChatComponent,
	CopilotChatEvents as CopilotChatWidgetEvents,
	CopilotChatMessageStatus,
} from 'ai.copilot-chat.ui';
import type { CopilotChatOptions, CopilotChatMessage } from 'ai.copilot-chat.ui';
import { CopilotChatApi, CopilotChatApiEvents } from './copilot-chat-api';

type CoreCopilotChatOptions = {
	scenarioCode: string;
	entityType: string;
	entityId: string;
	chatId?: ?number;
	initChatExtraOptions: Object;
	chatOptions: CopilotChatOptions;
};

export class CopilotChat extends EventEmitter
{
	#chatInstance: CopilotChatComponent;
	#scenarioCode: string;
	#entityType: string;
	#entityId: string;
	#chatOptions: CopilotChatOptions;
	#chatAPI: CopilotChatApi;
	#isHistoryFetched: boolean = false;
	#isLoadAllMessages: boolean = false;

	constructor(options: CoreCopilotChatOptions)
	{
		super(options);
		this.setEventNamespace('AI.CopilotChat');

		this.#validateOptions(options);

		this.#scenarioCode = options.scenarioCode;
		this.#entityType = options.entityType;
		this.#entityId = options.entityId;
		this.#chatOptions = options.chatOptions;

		this.#chatAPI = new CopilotChatApi({
			chatId: options.chatId || null,
			scenarioCode: this.#scenarioCode,
			entityType: this.#entityType,
			entityId: this.#entityId,
			initChatExtraOptions: options.initChatExtraOptions ?? {},
		});

		this.#initChatInstance();
	}

	show(): void
	{
		this.#chatInstance.show();

		if (this.#isHistoryFetched === false)
		{
			this.#initChat();
		}
	}

	hide(): void
	{
		this.#chatInstance?.hide();
	}

	isShown(): boolean
	{
		return Boolean(this.#chatInstance?.isShown());
	}

	#initChatInstance(): CopilotChatComponent
	{
		this.#chatInstance = new CopilotChatComponent({
			showCopilotWarningMessage: true,
			useChatStatus: true,
			scrollToTheEndAfterFirstShow: true,
			...this.#chatOptions,
		});

		this.#chatInstance.subscribe(CopilotChatWidgetEvents.ADD_USER_MESSAGE, async (event) => {
			const message: CopilotChatMessage = event.getData().message;

			this.#sendMessage(message);
		});

		this.#chatInstance.subscribe(CopilotChatWidgetEvents.RETRY_LOAD_HISTORY, async (event) => {
			this.#initChat();
		});

		this.#chatInstance.subscribe(CopilotChatWidgetEvents.MESSAGES_SCROLL_TOP, async () => {
			if (this.#chatInstance.isOldMessagesLoading() || this.#isLoadAllMessages)
			{
				return;
			}

			await this.#loadOldMessages();
		});

		this.#chatInstance.subscribe(CopilotChatWidgetEvents.RETRY_SEND_MESSAGE, async (event) => {
			const message = this.#chatInstance.getMessageById(event.getData().messageId);

			this.#sendMessage(message);
		});

		this.#chatInstance.subscribe(CopilotChatWidgetEvents.REMOVE_MESSAGE, (event) => {
			const messageId = event.getData().messageId;

			this.#chatInstance.removeMessage(messageId);
		});

		this.#chatInstance.subscribe(CopilotChatWidgetEvents.CLICK_ON_MESSAGE_BUTTON, async (event) => {
			const messageId = event.getData().messageId;
			const button = event.getData().button;

			this.#chatInstance.addUserMessage({
				id: -parseInt(Math.random() * 1000, 10),
				content: button.text,
				type: 'ButtonClicked',
				params: {
					messageId,
					buttonId: button.id,
				},
			});
		});

		this.#chatAPI.subscribe(CopilotChatApiEvents.INIT_CHAT, (event) => {
			this.emit(CopilotChatEvents.INIT_CHAT, event);
		});

		this.#chatAPI.subscribe(CopilotChatApiEvents.NEW_MESSAGE, (event) => {
			this.emit(CopilotChatEvents.NEW_MESSAGE, event);
		});

		this.#chatAPI.subscribe(CopilotChatApiEvents.NEW_MESSAGE, (event) => {
			const message = event.getData().message;

			if (this.#chatInstance.isMessageInList(message.id))
			{
				return;
			}

			this.#addChatMessage(message);
		});

		this.#chatAPI.subscribe(CopilotChatApiEvents.INPUT_STATUS_CHANGED, (event) => {
			const { status } = event.getData();

			switch (status)
			{
				case 'Lock':
				{
					this.#chatInstance.disableInput();
					this.#chatInstance.setCopilotWritingStatus(false);

					break;
				}

				case 'Unlock':
				{
					this.#chatInstance.enableInput();
					this.#chatInstance.setCopilotWritingStatus(false);

					break;
				}

				case 'Writing':
				{
					this.#chatInstance.setCopilotWritingStatus(true);
					this.#chatInstance.disableInput();

					break;
				}

				default:
				{
					console.warn('AI.CopilotChat.Core: Unknown input status', status);
				}
			}
		});

		this.#chatInstance.subscribe(CopilotChatWidgetEvents.MESSAGES_SCROLL_TOP, () => {
			// todo make loading messages
		});

		return this.#chatInstance;
	}

	async #loadOldMessages(): void
	{
		this.#chatInstance.startLoadingOldMessages();
		const messages = await this.#chatAPI.loadMessages(this.#chatInstance.getFirstMessageId());

		if (messages.length === 0)
		{
			this.#isLoadAllMessages = true;
		}
		this.#chatInstance.finishLoadingOldMessages();
		this.#chatInstance.unshiftMessages(messages);
	}

	async #initChat(): void
	{
		try
		{
			if (this.#chatInstance.isShown())
			{
				this.#chatInstance.hideLoadHistoryError();
				this.#chatInstance.showLoader();
			}

			const data = await this.#chatAPI.initChatData();

			this.#chatInstance.setUserAvatar(data.userPhoto);

			const messages = data.messages;

			messages.forEach((message) => {
				this.#addChatMessage(message, false);
			});

			this.#isHistoryFetched = true;
		}
		catch (e)
		{
			this.#chatInstance.showLoadHistoryError();
			console.error(e);
		}
		finally
		{
			this.#chatInstance.hideLoader();
		}
	}

	#addChatMessage(message: any): void
	{
		const chatMessage: CopilotChatMessage = {
			authorId: message.authorId,
			content: message.content,
			type: message.type,
			status: CopilotChatMessageStatus.DEPART,
			id: message.id,
			params: message.params ?? [],
		};

		if (message.isSystem === true)
		{
			this.#chatInstance.addSystemMessage(chatMessage, false);
		}
		else if (message.authorId === 0)
		{
			this.#chatInstance.addBotMessage(chatMessage, false);
		}
		else if (message.authorId > 0)
		{
			this.#chatInstance.addUserMessage(chatMessage, false);
		}
	}

	async #sendMessage(message: any): void
	{
		this.#chatInstance.setMessageStatusDepart(message.id);

		this.#chatInstance.disableInput();
		try
		{
			const data = await this.#chatAPI.sendMessage({
				content: message.content,
				messageId: message.params?.messageId ?? null,
				buttonId: message.params?.buttonId ?? null,
			});

			this.#chatInstance.setMessageId(message.id, data.message.id);
			this.#chatInstance.setMessageDate(data.message.id, data.message.dateCreate);
			this.#chatInstance.setMessageStatusDelivered(data.message.id);
		}
		catch
		{
			this.#chatInstance.setMessageStatusError(message.id);
			this.#chatInstance.enableInput();
		}
	}

	#validateOptions(options: CoreCopilotChatOptions = {}): void
	{
		const scenarioCode = options.scenarioCode;
		const entityType = options.entityType;
		const entityId = options.entityId;

		if (Type.isStringFilled(scenarioCode) === false)
		{
			throw new TypeError('scenarioCode option is required and must be the string');
		}

		if (Type.isStringFilled(entityType) === false)
		{
			throw new TypeError('entityType option is required and must be the string');
		}

		if (Type.isStringFilled(entityId) === false)
		{
			throw new TypeError('entityId option is required and must be the string');
		}
	}
}

export const CopilotChatEvents = {
	INIT_CHAT: 'initChat',
	NEW_MESSAGE: 'newMessage',
};
