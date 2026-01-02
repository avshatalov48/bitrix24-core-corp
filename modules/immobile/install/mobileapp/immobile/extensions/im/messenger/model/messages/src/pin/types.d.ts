import { MessagesModelState } from '../types/messages';
import { MessengerModel, PayloadData } from '../../../base';
import { SyncRawMessage, SyncRawPin } from '../../../../provider/services/sync/types/sync-list-result';

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
	pins?: Array<SyncRawPin>
	messages?: Array<SyncRawMessage>
}

declare type PinSetPayload = {
	pin: SyncRawPin,
	messages: Array<SyncRawMessage>,
	actionName?: string,
}

declare type PinSetListPayload = {
	pins: Array<SyncRawPin>,
	messages: Array<SyncRawMessage>,
	actionName?: string,
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
	| 'messagesModel/pinModel/setListFromSync'
	| 'messagesModel/pinModel/delete'
	| 'messagesModel/pinModel/deleteByIdList'
	| 'messagesModel/pinModel/deleteByIdListFromSync'
	| 'messagesModel/pinModel/updateMessage'
	| 'messagesModel/pinModel/updateMessages'
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
	| 'messagesModel/pinModel/updateMessages'

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
export type PinUpdateMessageData = {
	id?: number,
	chatId?: number,
	fields?: Partial<MessagesModelState>,
}

export interface PinUpdateMessagesData extends PayloadData
{
	messageList?: Array<PinUpdateMessageData>,
}
