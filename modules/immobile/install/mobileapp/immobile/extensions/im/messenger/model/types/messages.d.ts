import { ReactionsModelState } from './messages/reactions';

export type MessagesModelState = {
	id: number | string,
	templateId: string,
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
	forward: object,
	reactions?: ReactionsModelState // extended property
	attach: Array<AttachConfig>,
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
	| 'messagesModel/add'
	| 'messagesModel/addToChatCollection'
	| 'messagesModel/setPinned'
	| 'messagesModel/updateWithId'
	| 'messagesModel/update'
	| 'messagesModel/delete'
	| 'messagesModel/setReaction'
	| 'messagesModel/addReaction'
	| 'messagesModel/removeReaction'
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
