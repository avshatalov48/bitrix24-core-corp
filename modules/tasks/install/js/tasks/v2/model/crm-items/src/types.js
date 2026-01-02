export type CrmItemsModelState = {
	collection: { [crmItemId: string]: CrmItemModel },
};

export type CrmItemModel = {
	id: string,
	entityId: number,
	type: string,
	typeName: string,
	title: string,
	link: string,
};
