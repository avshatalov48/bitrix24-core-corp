import type { ProviderCodeType } from 'sign.v2.b2e.company-selector';

export type GeneralField = {
	type: 'date' | 'number' | 'string';
	name: string;
	uid: string;
}

export type ListField = GeneralField & {
	type: 'list';
	items: Array<{
		label: string;
		code: string;
	}>;
}

export type TemplateField = GeneralField | ListField;

export type Template = {
	uid: string,
	title: string,
	company: {
		name: string,
		rqInn: string,
	},
	providerCode: ProviderCodeType,
	fields: Array<TemplateField>
};
