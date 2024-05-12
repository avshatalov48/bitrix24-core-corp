import {MessagesModelState} from "../messages";
import {MessengerModel, PayloadData} from "../base";
import {RawMessage, RawPin} from "../../../provider/service/src/types/sync-list-result";

declare type PinModelState = {
	id: number,
	messageId: number,
	chatId: number,
	authorId: number,
	dateCreate: Date,
	message: MessagesModelState
};

declare type Pin = Omit<PinModelState, 'message'>


declare type PinModelCollection = {
	collection: Record<number, Array<Pin>>
	messageCollection: Record<number, MessagesModelState>
}

export type PinMessengerModel = MessengerModel<PinModelCollection>;

declare type PinSetChatCollectionPayload = {
	pins?: Array<RawPin>
	messages?: Array<RawMessage>
}

declare type PinSetPayload = {
	pin: RawPin,
	messages: Array<RawMessage>,
}

declare type PinSetListPayload = {
	pins: Array<RawPin>,
	messages: Array<RawMessage>,
}

declare type PinDeletePayload = {
	chatId: number,
	messageId: number,
}

export type PinModelActions =
	'messagesModel/pinModel/setChatCollection'
	| 'messagesModel/pinModel/setFromLocalDatabase'
	| 'messagesModel/pinModel/set'
	| 'messagesModel/pinModel/setList'
	| 'messagesModel/pinModel/delete'
	| 'messagesModel/pinModel/deleteByIdList'
	| 'messagesModel/pinModel/updateMessage'
	| 'messagesModel/pinModel/deleteMessagesByChatId'
	| 'messagesModel/pinModel/deleteMessagesByIdList'
	| 'messagesModel/pinModel/deleteMessage'


export type PinModelMutation =
	'messagesModel/pinModel/setChatCollection'
	| 'messagesModel/pinModel/add'
	| 'messagesModel/pinModel/updatePin'
	| 'messagesModel/pinModel/delete'
	| 'messagesModel/pinModel/deleteByChatId'
	| 'messagesModel/pinModel/deleteByIdList'
	| 'messagesModel/pinModel/deleteMessagesByIdList'
	| 'messagesModel/pinModel/updateMessage'

export type PinSetChatCollectionActions = 'setChatCollection' | 'setFromLocalDatabase';
export interface PinSetChatCollectionData extends PayloadData
{
	pins: Array<Pin>;
	messages: Array<MessagesModelState>;
	chatId: number;
}

export type PinAddActions = 'add';
export interface PinAddData extends PayloadData
{
	pin: Pin;
	message: MessagesModelState;
	chatId: number;
}

export type PinUpdatePinActions = 'set';
export interface PinUpdatePinData extends PayloadData
{
	pin: Pin;
	chatId: number;
}

export type PinDeleteActions = 'delete';
export interface PinDeleteData extends PayloadData
{
	messageId: number,
	chatId: number,
}

export type PinDeleteByIdListActions = 'deleteByIdList';
export interface PinDeleteByIdListData extends PayloadData
{
	idList: Array<number>;
}

export type PinDeleteByChatIdActions = 'deleteByChatId';
export interface PinDeleteByChatIdData extends PayloadData
{
	chatId: number,
}

export type PinDeleteMessagesByIdListActions = 'deleteMessage' | 'deleteMessagesByIdList' | 'updateMessage';
export interface PinDeleteMessagesByIdListData extends PayloadData
{
	idList: Array<number>;
}

export type PinUpdateMessageActions = 'updateMessage';
export interface PinUpdateMessageData extends PayloadData
{
	id: number,
	chatId: number,
	fields: Partial<MessagesModelState>
}