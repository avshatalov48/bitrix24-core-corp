import type Dictionary from './type/dictionary';
import type Options from './type/options';

const request = (action, data = {}) => {
	return BX.ajax.runAction(
		action,
		{
			data,
			headers: [{name: 'Content-Type', value: 'application/json'}]
		}
	).then(response => response.data);
};

export function prepareOptions(options: Type.Options, preparing: Type.Preparing): Promise
{
	return request('crm.api.form.options.prepare', preparing);
}

export function get(id: number): Promise
{
	return request('crm.api.form.get', {id});
}

export function save(options: Type.Options): Promise
{
	return request('crm.api.form.save', {options});
}

export function getDict(): Promise
{
	return request('crm.api.form.getDict');
}

export type {
	Dictionary,
	Options,
};