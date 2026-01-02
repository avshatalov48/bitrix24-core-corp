import type { ProviderCodeType, TemplateEntity } from 'sign.type';
import type { LoadedDocumentData } from 'sign.v2.api';

export type GeneralField = {
	type: 'date' | 'number' | 'string';
	name: string;
	uid: string;
	value: string;
}

export type ListField = GeneralField & {
	type: 'list';
	items: Array<{
		label: string;
		code: string;
	}>;
}

export type AddressField = GeneralField & {
	type: 'address';
	subfields?: GeneralField[];
};

export type TemplateField = GeneralField | ListField | AddressField;

export type Template = {
	uid: string,
	title: string,
	company: {
		id: number,
		name: string,
		taxId: string,
	},
	providerCode: ProviderCodeType,
	isLastUsed: boolean,
};

export type TemplateFolder = {
	id: number;
	title: string;
	createdById: number;
	modifiedById: number;
	dateCreate: Object;
	dateModify: Object;
	status: string;
	visibility: 'visible' | 'invisible';
}

export type TemplateSelectedEntity = {
	id: number;
	entityType: TemplateEntity.template | TemplateEntity.folder;
};

export type FieldValue = {
	name: string,
	value: string,
};

export type TemplateCreatedDocument = {
	templateId: number,
	document: LoadedDocumentData,
};
