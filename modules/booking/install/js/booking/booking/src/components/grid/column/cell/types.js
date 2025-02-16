export type CellDto = {
	id: string,
	minutes: number,
	fromTs: number,
	toTs: number,
	resourceId: number,
};

export type CellData = {
	hovered: boolean,
	halfOffset: number,
};
