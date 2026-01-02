import { EventEmitter } from 'main.core.events';
import { ajax } from 'main.core';
import { PULL, PullClient } from 'pull.client';

export type CopilotChatApiOptions = {
	scenarioCode: string;
	entityType?: string;
	entityId?: string;
	chatId?: number;
	initChatExtraOptions?: Object;
};

export class CopilotChatApi extends EventEmitter
{
	#scenarioCode: string;
	#entityType: string;
	#entityId: string;
	#chatId: ?number;
	#initChatExtraOptions: Object;

	constructor(options: CopilotChatApiOptions)
	{
		super(options);

		this.setEventNamespace('AI.CopilotChatAPI');
		this.#scenarioCode = options.scenarioCode;
		this.#entityType = options.entityType;
		this.#entityId = options.entityId;
		this.#chatId = options.chatId;
		this.#initChatExtraOptions = options.initChatExtraOptions ?? {};

		this.#initPull();
	}

	async #initPull(): void
	{
		try
		{
			await PULL.start();

			PULL.subscribe({
				type: PullClient.SubscriptionType.Server,
				moduleId: 'ai',
				callback: (data) => {
					if (data.command === CopilotChatApiPullEvents.NEW_MESSAGE)
					{
						this.#handleNewMessageEvent(data.params);
					}
					else if (data.command === CopilotChatApiPullEvents.INPUT_STATUS_CHANGED)
					{
						this.#handleInputStatusChangedEvent(data.params);
					}
				},
			});
		}
		catch (e)
		{
			console.error(e);
		}
	}

	#handleNewMessageEvent(data: CopilotChatPullEventNewMessageData): void
	{
		if (data.message.chatId !== this.#chatId)
		{
			return;
		}

		this.emit(CopilotChatApiEvents.NEW_MESSAGE, data);
	}

	#handleInputStatusChangedEvent(data: Object): void
	{
		this.emit(CopilotChatApiEvents.INPUT_STATUS_CHANGED, data);
	}

	async initChatData(): Promise<InitChatDataResponseData>
	{
		const data = {
			scenarioCode: this.#scenarioCode,
			entityType: this.#entityType,
			entityId: this.#entityId,
			parameters: this.#initChatExtraOptions,
		};

		if (this.#chatId)
		{
			data.chatId = this.#chatId;
		}

		const result = await ajax.runAction('ai.chat.init', {
			data,
		});

		data.chatId = result.data.chat.id;
		this.emit(CopilotChatApiEvents.INIT_CHAT, data);

		this.#chatId = result.data.chat.id;

		return result.data;
	}

	async sendMessage(messageData: ChatSendMessageData): Promise<ChatSendMessageResponseData>
	{
		const data = {
			messageData,
			scenarioCode: this.#scenarioCode,
			chatId: this.#chatId,
		};

		const result = await ajax.runAction('ai.chat.sendMessage', {
			data,
		});

		return result.data;
	}

	async loadMessages(offsetMessageId: number): Promise<ChatDataMessage[]>
	{
		const result = await ajax.runAction('ai.chat.getMessages', {
			data: {
				chatId: this.#chatId,
				offsetMessageId,
				limit: 20,
			},
		});

		return result.data.messages ?? [];
	}
}

export type InitChatDataResponseData = {
	chat: {
		id: number;
		inputStatus: "Lock" | "Unlock"
	};
	messages: ChatDataMessage[];
	userPhoto: ?string;
}

export type ChatSendMessageData = {
	content: string;
	messageId?: number;
	buttonId?: number;
};

export type ChatSendMessageResponseData = {
	message: ChatDataMessage,
}

export type ChatDataMessage = {
	authorId: number;
	chatId: number;
	content: string;
	dateCreate: string;
	id: 2;
	isSystem: false;
	isViewed: false;
	type: string;
	params: Object;
}

export const CopilotChatApiEvents = {
	INIT_CHAT: 'initChat',
	NEW_MESSAGE: 'newMessage',
	INPUT_STATUS_CHANGED: 'InputStatusChangedEvent',
};

const CopilotChatApiPullEvents = Object.freeze({
	NEW_MESSAGE: 'newMessage',
	INPUT_STATUS_CHANGED: 'InputStatusChanged',
});

type CopilotChatPullEventNewMessageData = {
	chat: {
		inputStatus: "Unlock";
	};
	message: {
		authorId: number;
		chatId: number;
		content: number;
		dateCreate: string;
		id: 46;
		isSystem: false;
		isViewed: true;
		params: [];
		type: "Default";
	}
};
