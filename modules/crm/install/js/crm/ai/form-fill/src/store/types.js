export interface FormFieldsToMergeResult {
	target: EntityInfo,
	editMode: boolean;
	fields: FieldInfo[];
	entity: {
		entityId: number;
		entityTypeName: string;
		editorId: string;
	}
}

export interface FieldInfo {
	name: string;
	type: string;
	title: string;
	aiModel: UserFieldModel;
	isMultiple: boolean;
	isUserField: boolean;
}

export interface EntityInfo {
	entityTypeName: string;
	entityTypeId: number;
	entityId: number;
	categoryId: ?number;
	editorId: string;
	feedbackWasSent: boolean;
}

export interface ConflictField extends FieldInfo {
	aiValue: any;
	originalValue: any;
	originalModel: ?UserFieldModel,
	isAiValuesUsed: boolean;
	order: number;
}

export interface UserFieldModel {
	VALUE: any;
	IS_EMPTY: boolean;
	SIGNATURE: string;
}

export interface EditorControlsParams {
	fieldId: string;
	relatedFieldOffsetY: number;
	originalValue: any;
	originalModel: ?UserFieldModel,
	order: number;
}

export const FEEDBACK_TRIGGER_CONTROL = 'FEEDBACK_TRIGGER_CONTROL';
export const FEEDBACK_TRIGGER_APP_CLOSE = 'FEEDBACK_TRIGGER_APP_CLOSE';

