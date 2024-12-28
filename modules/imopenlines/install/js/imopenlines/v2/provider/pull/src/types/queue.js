import type { QueueTypeName } from 'imopenlines.v2.const';

export type QueueUpdateParams = {
	id: number,
	name: string,
	PRIORITY: number,
	queue_type: QueueTypeName,
}
