import type { StatusGroupName } from 'imopenlines.v2.const';

export type Session = {
	id: number,
	chatId: number,
	operatorId: number,
	status: StatusGroupName,
	queueId: number,
	pinned: boolean,
	isClosed: boolean,
};
