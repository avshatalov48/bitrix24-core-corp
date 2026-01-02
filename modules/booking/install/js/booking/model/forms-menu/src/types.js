export type FormsMenuState = FormsMenuModel;

export type FormsMenuModel = {
	canEdit: boolean;
	createFormLink: string;
	formList: FormsMenuItem[];
	formsListLink: string;
}

export type FormsMenuItem = {
	editUrl: string;
	id: string;
	name: string;
	publicUrl: string;
}
