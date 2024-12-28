import { Core } from 'im.v2.application.core';

export const QueueType = {
	all: 'all',
	strictly: 'strictly',
	evenly: 'evenly',
};

export type QueueTypeName = $Values<typeof QueueType>;

export const QueueManager = {
	getQueueType(queueId: string): ?QueueTypeName
	{
		const { queueConfig = {} } = Core.getApplicationData();

		const queueItem = Object.values(queueConfig).find((queue) => queue.id === queueId);

		return queueItem ? queueItem.type : null;
	},
};
