export type CompanyEditorOptions = {
	documentEntityId: number;
	companyId?: string,
	mode?: string,
	events?: {
		onCompanySavedHandler?: (companyId: number) => void,
	},
	layoutTitle?: string,
}

export const CompanyEditorMode = Object.freeze({
	Edit: 'edit',
	Create: 'create',
});