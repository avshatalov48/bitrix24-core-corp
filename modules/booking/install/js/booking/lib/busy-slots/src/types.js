export type BusySlotDto = {
	id: string,
	fromTs: number,
	toTs: number,
	resourceId: number,
	intersectingResourceId?: number,
	type: string,
};

export type Range = {
	from: number,
	to: number,
	id: number,
};
