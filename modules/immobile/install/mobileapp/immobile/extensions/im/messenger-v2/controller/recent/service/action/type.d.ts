import {RecentWidgetItemAction} from "../../../../../messenger/lib/element/recent/types/base";
import {RecentWidgetItem} from "../../../../../messenger/view/recent/types/recent-item";

export interface IActionService {
	onItemAction: (itemActionData: ItemActionEventData) => void;
}

export type ItemActionEventData = {
	action: RecentWidgetItemAction,
	item: RecentWidgetItem
}
