import {EditableType} from './list-editor-options';

export type ListEditorItemOptions = {
	sourceData: {
		type: string,
		entity_field_name: string,
		entity_name: string,
		name: string,
		caption: string,
		multiple: boolean,
		required: boolean,
	},
	data: {
		name: string,
		label: string,
		multiple: boolean,
		required: boolean,
	},
	categoryCaption: string,
	editable: {
		[property: string]: {
			label: string,
			type: EditableType,
		},
	},
	events?: {
		onChange: () => void,
	},
};