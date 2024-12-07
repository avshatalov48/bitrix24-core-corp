export type CompanyEditorOptions = {
	documentEntityId: number;
	entityTypeId: number;
	guid: string;
	companyId?: string,
	mode?: string,
	showOnlyCompany: boolean,
	events?: {
		onCompanySavedHandler?: (companyId: number) => void,
	},
	layoutTitle?: string,
}

export const CompanyEditorMode: Record<string, string> = Object.freeze({
	Edit: 'edit',
	Create: 'create',
});

export const DocumentEntityTypeId: Record<string, number> = Object.freeze({
	B2b: 36,
	B2e: 39,
});

export const EditorTypeGuid: Record<string, string> = Object.freeze({
	B2b: 'sign_b2b_entity_editor',
	B2e: 'sign_b2e_entity_editor',
});
