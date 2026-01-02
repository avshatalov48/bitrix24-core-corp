export type CrmFormResourceModel = {
	id: number,
	name: string,
	typeName: string,
	slotRanges: CrmFormResourceSlotRange[],
}

export type CrmFormResourceSlotRange = {
	id: number,
	from: number,
	to: number,
	slotSize: number,
	timezone: string,
	weekDays: string[],
}
