export type EntityCounterPanelOptions = {
	id: string,
	entityTypeId: number,
	entityTypeName: string,
	userId: number,
	userName: string,
	serviceUrl: string,
	data: Array,
	codes: Array,
	extras: Object,
	withExcludeUsers: ?boolean,
	filterLastPresetId: String,
	filterLastPresetData: Array,
	lockedCallback: String,
	filterResponsibleFiledName: string
};
