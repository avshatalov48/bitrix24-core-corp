declare type ChatId = number;

declare type CounterState = {
	chatId: number,
	parentChatId: number,
	counter: number,
	type: string,
};

declare type CounterStateCollection = Record<ChatId, CounterState>;
