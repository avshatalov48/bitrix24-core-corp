export type Resource = {
	id: number,
	name: string,
	typeName: string,
	slotRanges: SlotRange[];
}

export type SlotRange = {
	id: number,
	from: number,
	to: number,
	timezone: string,
	weekDays: number[],
	slotSize: number,
}

export type ResourceSlot = {
	fromTs: number,
	toTs: number,
	label: string,
}

export type ResourceOccupancy = {
	fromTs: number;
	toTs: number;
}
