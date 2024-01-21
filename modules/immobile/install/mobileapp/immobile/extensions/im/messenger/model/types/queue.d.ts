export type QueueModelState = {
	requestName: string,
	requestData: object,
	priority: number,
	messageId: number,
};

export type QueueModelActions =
	'queueModel/add'
	| 'queueModel/delete'

export type QueueModelMutation =
	'queueModel/add'
	| 'queueModel/deleteById'
