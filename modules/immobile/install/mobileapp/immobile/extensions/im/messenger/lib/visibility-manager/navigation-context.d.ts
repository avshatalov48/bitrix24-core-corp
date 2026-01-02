import {DialogId} from "../../types/common";

export type NavigationContext = {
	children: Array<object>,
	isTabActive: boolean,
	itemsInStack: Array<object>,
	navigationIsVisible: boolean,
};

export type VisibleDialogInfo = {
	dialogId: DialogId,
	dialogCode: string,
	parentComponentCode: string,
}

export type ActiveTabInfo = {
	tabId: string,
	componentCode: string,
}
