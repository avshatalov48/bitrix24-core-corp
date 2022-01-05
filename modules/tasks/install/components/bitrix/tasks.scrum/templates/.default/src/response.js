import {Item} from './item/item';

export type ShowLinkedTasksResponse = {
	data: {
		items: Array<Item>,
		linkedItemIds: Array<number>
	}
}
