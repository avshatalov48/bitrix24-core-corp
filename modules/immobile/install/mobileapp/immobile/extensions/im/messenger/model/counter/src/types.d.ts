import { MessengerModel, PayloadData } from '../../base';

declare type CounterModelState = {
	chatId: number,
	parentChatId: number,
	type: string,
	counter: number,
	locked: boolean,
	disabled: boolean,
}

declare type CounterModelCollection = {
	collection: Record<number, CounterModelState>,
}

export type CounterMessengerModel = MessengerModel<CounterModelCollection>;

export type CounterModelActions = 'counterModel/setList'
	| 'counterModel/readChildChatsCounters'
	| 'counterModel/lockChatCounter'
	| 'counterModel/unlockChatCounter'
	| 'counterModel/disableChatCounter'
	| 'counterModel/enableChatCounter'
	| 'counterModel/setDisableChatCounter'
	| 'counterModel/delete'
	| 'counterModel/clear'
;

export type CounterModelMutation = 'counterModel/set'
	| 'counterModel/setLock'
	| 'counterModel/setDisable'
	| 'counterModel/delete'
;

declare type CounterDeleteActions = 'delete' | 'clear';
export interface CounterDeleteData extends PayloadData
{
	chatIdList: Array<number>;
}
declare type CounterSetActions = 'setList'
	| 'readChildChatsCounters'
;
export interface CounterSetData extends PayloadData
{
	counterList: Array<CounterModelState>
}

export type CounterSetLockActions = 'lockChatCounter' | 'unlockChatCounter';
export interface CounterSetLockData extends PayloadData
{
	chatId: number,
	locked: boolean,
}

export type CounterSetDisableActions = 'disableChatCounter'
	| 'enableChatCounter'
	| 'setDisableChatCounter'
;
export interface CounterSetDisableData extends PayloadData
{
	chatId: number,
	disabled: boolean,
}
