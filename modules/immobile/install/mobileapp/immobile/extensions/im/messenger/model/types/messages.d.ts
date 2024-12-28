import { ReactionsModelState } from './messages/reactions';
import {MessengerModel, PayloadData} from "./base";
import {DialogType} from "./dialogues";
import {KeyboardButtonConfig} from "./messages/keyboard";

declare type MessagesModelCollection = {
	collection: Record<number | string, MessagesModelState>,
	chatCollection: Record<number, Set<number>>,
	temporaryMessages: Record<string, MessagesModelState>,
	uploadingMessageCollection: Set<string>,
}

export type MessagesMessengerModel = MessengerModel<MessagesModelCollection>;

export type MessagesModelState = {
	id: number | string,
	templateId: string,
	previousId: number,
	nextId: number,
	chatId: number,
	authorId: number,
	date: Date,
	text: string,
	loadText: string,
	params?: {
		ATTACH?: Array<Object>,
		TS: string,
		REACTION: Object //don't use. See property reactions
		URL_ONLY?: 'Y' | 'N',
		URL_ID?: Array<string>,
		componentId: string,
	},
	replaces: Array<Object>,
	files: Array<number | string>,
	unread: boolean,
	viewed: boolean,
	viewedByOthers: boolean,
	sending: boolean,
	error: boolean,
	errorReason: number,
	retry: boolean,
	audioPlaying: boolean,
	playingTime: number,
	forward?: {
		id: string,
		userId: number,
		chatTitle: string | null,
		chatType: DialogType,
	},
	reactions?: ReactionsModelState // extended property
	attach: Array<AttachConfig>,
	keyboard: Array<KeyboardButtonConfig>,
	richLinkId: number,
}

declare type AttachConfig = {
	id: string,
	description: string,
	color: string,
	blocks: Array<AttachBlock>
}

declare type AttachBlock = {
	delimiter?: AttachDelimiterBlock,
	file?: AttachFileBlock,
	grid?: AttachGridBlock,
	html?: AttachHtmlBlock,
	image?: AttachImageBlock,
	link?: AttachLinkBlock,
	message?: AttachMessageBlock,
	richLink?: AttachRichBlock,
	user?: AttachUserBlock,
}

declare type AttachMessageBlock = string;

declare type AttachDelimiterBlock = {
	size?: string,
	color?: string,
}

declare type AttachFileBlock = Array<AttachFileItem>

declare type AttachFileItem = {
	link: string,
	name?: string,
	size?: number
};

declare type AttachGridBlock = Array<AttachGridItem>

declare type AttachGridItem = {
	display: AttachGridItemDisplayType,
	name: string,
	value: string,
	width?: number,
	color?: string,
	link?: string
};

declare enum AttachGridItemDisplayType
{
	block = 'BLOCK',
	line = 'LINE',
	row = 'ROW'
}

declare type AttachHtmlBlock = string;

declare type AttachImageBlock = Array<AttachImageItem>;

declare type AttachImageItem = {
	link: string,
	width?: number,
	height?: number,
	name?: string,
	preview?: string
};

declare type AttachLinkBlock = Array<AttachLinkItem>

declare type AttachLinkItem = {
	link: string,
	name?: string,
	desk?: string,
	html?: string,
	preview?: string,
	width?: number,
	height?: number
};

declare type AttachUserBlock = Array<AttachUserItem>

declare type AttachUserItem = {
	name: string,
	avatar: string,
	avatarType: string,
	link: string
};

declare type AttachRichBlock = Array<AttachRichItem>;

declare type AttachRichItem = {
	link: string,
	name?: string,
	desc?: string,
	html?: string,
	preview?: string
	previewSize?: {
		height: number,
		width: number
	}
};

export type MessagesModelActions =
	'messagesModel/forceUpdateByChatId'
	| 'messagesModel/store'
	| 'messagesModel/storeToLocalDatabase'
	| 'messagesModel/setFromLocalDatabase'
	| 'messagesModel/add'
	| 'messagesModel/addToChatCollection'
	| 'messagesModel/setPinned'
	| 'messagesModel/updateWithId'
	| 'messagesModel/update'
	| 'messagesModel/updateParams'
	| 'messagesModel/delete'
	| 'messagesModel/readMessages'
	| 'messagesModel/setViewedByOthers'
	| 'messagesModel/updateLoadTextProgress'
	| 'messagesModel/setChatCollection'
	| 'messagesModel/deleteByIdList'
	| 'messagesModel/setTemporaryMessages'
	| 'messagesModel/deleteTemporaryMessage'
	| 'messagesModel/deleteTemporaryMessages'
	| 'messagesModel/deleteByChatId'
	| 'messagesModel/deleteAttach'
	| 'messagesModel/clearChatCollection'
	| 'messagesModel/disableKeyboardByMessageId'

export type MessagesModelMutation =
	'messagesModel/setChatCollection'
	| 'messagesModel/store'
	| 'messagesModel/setPinned'
	| 'messagesModel/updateWithId'
	| 'messagesModel/update'
	| 'messagesModel/delete'
	| 'messagesModel/deleteByChatId'
	| 'messagesModel/clearCollection'
	| 'messagesModel/setTemporaryMessages'
	| 'messagesModel/deleteTemporaryMessage'
	| 'messagesModel/deleteTemporaryMessages'

/* region mutation types */

export type MessagesSetChatCollectionActions =
	'forceUpdateByChatId'
	| 'setChatCollection'
	| 'add'
	| 'addToChatCollection'
;
export interface MessagesSetChatCollectionData extends PayloadData
{
	messageList: Array<MessagesModelState>;
}


export type MessagesStoreActions =
	'forceUpdateByChatId'
	| 'setChatCollection'
	| 'store'
	| 'add'
;
export interface MessagesStoreData extends PayloadData
{
	messageList: Array<MessagesModelState>;
}


export type MessagesSetTemporaryMessagesActions = 'setTemporaryMessages';
export interface MessagesSetTemporaryMessagesData extends PayloadData
{
	messageList: Array<MessagesModelState>;
}


export type MessagesSetPinnedActions = 'setPinned';
export interface MessagesSetPinnedData extends PayloadData
{
	chatId: number;
	pinnedMessageIds: Array<number>;
}


export type MessagesUpdateWithIdActions = 'updateWithId';
export interface MessagesUpdateWithIdData extends PayloadData
{
	id: number;
	fields: Partial<MessagesModelState>;
}


export type MessagesUpdateActions =
	'update'
	| 'readMessages'
	| 'setViewedByOthers'
	| 'updateLoadTextProgress'
	| 'setAudioState'
	| 'deleteAttach'
;
export interface MessagesUpdateData extends PayloadData
{
	id: number;
	fields: Partial<MessagesModelState>;
}


export type MessagesDeleteActions =
	'deleteByIdList'
	| 'delete'
;
export interface MessagesDeleteData extends PayloadData
{
	id: number;
}


export type MessagesDeleteByChatIdActions = 'deleteByChatId';
export interface MessagesDeleteByChatIdData extends PayloadData
{
	chatId: number;
}


export type MessagesDeleteTemporaryMessageActions = 'deleteTemporaryMessage';
export interface MessagesDeleteTemporaryMessageData extends PayloadData
{
	id: number
}


export type MessagesDeleteTemporaryMessagesActions = 'deleteTemporaryMessages';
export interface MessagesDeleteTemporaryMessagesData extends PayloadData
{
	ids: Array<number>;
}


export type MessagesClearCollectionActions = 'setChatCollection'
export interface MessagesClearCollectionData extends PayloadData
{
	chatId: number;
}

export type MessagesAddToUploadingCollectionActions = 'add'
export interface MessagesAddToUploadingCollectionData extends PayloadData
{
	id: string;
}

export type MessagesDeleteFromUploadingCollectionActions = 'delete' | 'updateWithId' | 'update'
export interface MessagesDeleteFromUploadingCollectionData extends PayloadData
{
	id: string;
}

/* endregion mutation types */
