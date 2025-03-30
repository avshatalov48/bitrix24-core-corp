import type { ProviderCodeType } from 'sign.type';

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

export type FieldValue = {
	name: string,
	value: string,
};
