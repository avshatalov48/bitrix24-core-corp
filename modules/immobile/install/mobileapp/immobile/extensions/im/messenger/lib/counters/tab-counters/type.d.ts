import {CounterModelState} from "../../../model/counter/src/types";

export interface ITabCounterController
{
	update(): void;
	addChatToMutedCollection(chatId: number): void;
	deleteChatFromMutedCollection(chatId: number): void;
	clearAll(): void;
	deleteCounterByChatId(chatId: number): void;
	updateCounterDetailByCounterState(counterState: CounterModelState): void;
}