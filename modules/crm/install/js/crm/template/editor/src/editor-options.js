export type EditorOptions = {
	id?: string,
	target: HTMLElement,
	entityTypeId: number,
	entityId: number,
	categoryId?: number,
	onSelect: () => {},
	onDeselect?: () => {},
	canUseFieldsDialog?: boolean,
	canUseFieldValueInput?: boolean,
};
