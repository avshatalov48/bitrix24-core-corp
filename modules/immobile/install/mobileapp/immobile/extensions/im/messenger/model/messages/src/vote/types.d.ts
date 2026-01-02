import { MessengerModel, PayloadData } from '../../../base';

type MessageId = number | string;

export type VoteModelState = {
	id: MessageId,
	messageId: MessageId,
	chatId: number,
	voteId: number,
	isFinished: boolean,
	isVoted: boolean,
	votedAnswerIds: Set<string>,
	votedCounter: number,
	questionVotedCounter: Map<string, number>,
	answerVotedCounter: Map<string, number>,
	answerVotedUserIds?: Map<string, string[]>,
	answerLocalSelection?: Map<string, Object<string, string[]>>,
	resultUrl?: string,
};

export type VoteSetActions =
	'add'
	| 'setFromLocalDatabase'
	| 'setFromResponse'
	| 'setFromPullEvent'
	| 'update'
;
export interface VoteSetData extends PayloadData
{
	voteList: Array<VoteModelState>;
}
export type VoteModelSetPayload = {
	votes: Array<VoteModelState>,
};

export type VoteUpdateWithIdActions = 'updateWithId';
export interface VoteUpdateWithIdData extends PayloadData
{
	id: string | number;
	vote: VoteModelState;
}
export type VoteModelUpdateWithIdPayload = {
	id: string | number,
	vote: VoteModelState,
};

export type VoteDeleteByChatIdActions = 'deleteByChatId';
export interface VoteDeleteByChatIdData extends PayloadData
{
	chatId: number;
}
export type VoteDeleteByChatIdPayload = {
	chatId: number,
};

declare type VoteModelCollection = {
	collection: Record<MessageId, VoteModelState>,
};
export type VoteMessengerModel = MessengerModel<VoteModelCollection>;

export type VoteModelActions =
	'messagesModel/voteModel/setFromLocalDatabase'
	| 'messagesModel/voteModel/setFromResponse'
	| 'messagesModel/voteModel/setFromPullEvent'
	| 'messagesModel/voteModel/add'
	| 'messagesModel/voteModel/updateWithId'
	| 'messagesModel/voteModel/deleteByChatId'
;

export type VoteModelMutation =
	'messagesModel/voteModel/set'
	| 'messagesModel/voteModel/updateWithId'
	| 'messagesModel/voteModel/deleteByChatId'
;
