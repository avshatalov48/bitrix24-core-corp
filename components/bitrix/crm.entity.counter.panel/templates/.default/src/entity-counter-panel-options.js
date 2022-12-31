export type EntityCounterPanelOptions = {
	id: string,
	entityTypeId: number,
	userId: number,
	userName: string,
	serviceUrl: string,
	data: Array,
	codes: Array,
	extras: Object,
	withExcludeUsers: ?boolean,
	filterLastPresetId: String,
	filterLastPresetData: Array,
	isNewCountersTourSeen: String
};
