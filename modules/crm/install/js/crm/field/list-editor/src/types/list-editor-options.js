export type EditableType = 'string' | 'boolean';

export type ListEditorOptions = {
	setId?: number,
	autoSave?: boolean,
	cacheable?: boolean,
	title?: string;
	editable: {
		[property: string]: {
			label: string,
			type: EditableType,
		},
	},
	events?: {
		onChange?: () => void,
		onDebounceChange?: () => void,
	},
	debouncingDelay?: number,
	fieldsPanelOptions: {
		disabledFields?: Array<string>,
		allowedCategories?: Array<string>,
		allowedTypes?: Array<string>,
		multiple?: boolean,
	},
};