export type HistoryItem = {
	id: number,
	date: string,
	data: string,
	payload: string,
	groupData: ?Array,
};

export type ErrorMessage = {
	message: string,
};

export type PickerOptions = {
	startMessage: string,
	moduleId: string,
	contextId: string,
	history: boolean,
	onSelect: () => {},
	popupContainer?: HTMLElement,
	analyticLabel: string;
	onTariffRestriction: Function;
	saveImages: boolean;
};
