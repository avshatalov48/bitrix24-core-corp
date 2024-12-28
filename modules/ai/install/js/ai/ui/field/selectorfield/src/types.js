export type SelectorFieldOptions = {
	inputName: string,
	label: string,
	name: string,
	items: [],
	additionalItems: ?[],
	recommendedItems: ?[],
	current: string,
}

export type SelectorFieldItemOption = {
	name: string,
	value: string,
	selected: boolean,
	recommended: ?boolean,
}