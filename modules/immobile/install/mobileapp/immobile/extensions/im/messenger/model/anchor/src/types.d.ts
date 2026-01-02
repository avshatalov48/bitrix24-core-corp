import { MessengerModel, PayloadData } from '../../base';

declare type AnchorModelActions = (
	'anchorModel/setState'
	| 'anchorModel/add'
	| 'anchorModel/delete'
	| 'anchorModel/deleteByChatId'
	| 'anchorModel/deleteByMessageId'
	| 'anchorModel/deleteByMessageIdList'
);

declare type AnchorModelState = {
	chatId: number,
	fromUserId: number,
	messageId: number,
	parentChatId: number,
	subType: string,
	type: 'MENTION' | 'REACTION',
	userId: number,
};

declare type AnchorModelMutation = (
	'anchorModel/setState'
	| 'anchorModel/add'
	| 'anchorModel/delete'
	| 'anchorModel/deleteMany'
);

export interface AnchorSetStateData extends PayloadData
{
	collection: Record<string, Array<AnchorModelState>>,
}

export interface AnchorAddData extends PayloadData
{
	anchor: AnchorModelState,
}

export interface AnchorDeleteData extends PayloadData
{
	anchor: AnchorModelState,
}

export interface AnchorDeleteManyData extends PayloadData
{
	anchorList: AnchorModelState[],
}

export type AnchorSetStateAction = 'setState';
export type AnchorAddAction = 'add';
export type AnchorDeleteAction = 'delete';
export type AnchorDeleteManyAction = 'deleteMany';

export type AnchorModelCollection = {
	collection: Record<string, Array<AnchorModelState>>,
}

export type AnchorMessengerModel = MessengerModel<AnchorModelCollection>;
