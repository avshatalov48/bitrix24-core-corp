import { MessengerModel, PayloadData } from '../../base';

export type QueueModelState = {
	id: string,
	requestName: string,
	requestData: object,
	priority: number,
	messageIdList: Array<number>,
};

export type QueueModelActions =
	'queueModel/add'
	| 'queueModel/delete'

export type QueueModelMutation =
	'queueModel/add'
	| 'queueModel/deleteById'

export type QueueAddActions = 'add';
export interface QueueAddData extends PayloadData
{
	requests: Array<QueueModelState>
}

export type QueueDeleteByIdActions = 'deleteById';
export interface QueueDeleteByIdData extends PayloadData
{
	requestsIds: Array<string>;
}

declare type QueueModelCollection = {
	collection: Array<QueueModelState>,
};

export type QueueModel = MessengerModel<QueueModelCollection>;
