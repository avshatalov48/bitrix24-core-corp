import type { DialogOptions } from 'ui.entity-selector';

export type EditorOptions = {
	id?: string,
	target: HTMLElement,
	entityTypeId: number,
	entityId: number,
	categoryId?: number,
	onSelect: () => {},
	onDeselect?: () => {},
	dialogOptions?: DialogOptions,
	usePlaceholderProvider?: boolean,
	canUseFieldsDialog?: boolean,
	canUseFieldValueInput?: boolean,
	canUsePreview: boolean,
};
