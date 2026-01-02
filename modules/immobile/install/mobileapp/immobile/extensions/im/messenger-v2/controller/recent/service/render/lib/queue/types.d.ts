import { RecentModelState } from '../../../../../../../messenger/model/recent/src/types';
import { UpsertOptions } from '../../types';

declare type RecentQueueOperation = 'setItems'
	| 'setPreparedItems'
	| 'removeItems'
	| 'removeItemsByIds'
	| 'upsertItems'
	| 'upsertPreparedItems'

declare type RecentQueueItem = {
	operation: RecentQueueOperation,
	items?: Array<RecentModelState>,
	preparedItems?: Array<RecentItem>,
	ids?: Array<string>,
	options?: UpsertOptions | object,
};
