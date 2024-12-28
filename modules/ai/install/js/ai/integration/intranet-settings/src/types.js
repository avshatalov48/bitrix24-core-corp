import {BaseField} from 'ui.form-elements.view';
import {SettingsRow} from 'ui.form-elements.field';

export type AiSettingsGroup = {
	code: string,
	title: string,
	description: ?string,
	helpdesk: ?number|string,
	icon: ?{
		set: string,
		code: string,
	},
	items: {[string]: AiSettingsItem},
	relations?: [AiSettingsItemRelations],
};

export type AiSettingsItem = {
	code: string,
	title: string,
	type: 'boolean'|'list',
	header: string,
	value: any,
	options: ?{[string]: string},
	recommended: ?[string],
	onSave: ?{
		title: string,
		callback: string,
	},
};

export type AiSettingsItemField = {
	code: string,
	field: BaseField,
	row: SettingsRow,
};

export type AiSettingsItemRelations = {
	parent: string,
	children: [string],
};
