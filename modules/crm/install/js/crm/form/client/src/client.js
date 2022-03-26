import {Cache, Type} from 'main.core';
import type {FormDictionary, PreparingOptions, FormOptions} from 'crm.form.type';
import request from './internal/request';
import type {RequestError} from './internal/request';

const instance = Symbol('instance');

/**
 * Crm-From client
 * Implements singleton pattern
 *
 * @example
 * import {Client} from 'crm.form.client';
 * const client = Client.getInstance();
 *
 * client
 * 		.loadOptionsById(formId)
 * 		.then((options) => {
 * 			// ...
 * 		});
 *
 * @memberOf BX.Crm.Form
 */
export class FormClient
{
	static getInstance(): FormClient
	{
		if (!FormClient[instance])
		{
			FormClient[instance] = new FormClient();
		}

		return FormClient[instance];
	}

	cache = new Cache.MemoryCache();

	getOptions(formId: number): Promise<FormOptions, RequestError>
	{
		return this.cache.remember(`formOptions#${formId}`, () => {
			return request({
				action: 'get',
				data: {id: formId},
			});
		});
	}

	getDictionary(): Promise<FormDictionary, RequestError>
	{
		return this.cache.remember('formDictionary', () => {
			return request({action: 'getDict'});
		});
	}

	// eslint-disable-next-line class-methods-use-this
	prepareOptions(options: FormOptions, preparing: PreparingOptions): Promise<any>
	{
		return request({action: 'prepare', data: {options, preparing}});
	}

	// eslint-disable-next-line class-methods-use-this
	saveOptions(options: FormOptions): Promise<any, RequestError>
	{
		return request({action: 'save', data: {options}});
	}

	// eslint-disable-next-line class-methods-use-this
	checkFields(options: FormOptions): Promise<any>
	{
		return request({action: 'check', data: {options}});
	}

	resetCache(formId?: number)
	{
		if (Type.isNumber(formId) || Type.isStringFilled(formId))
		{
			this.cache.delete(`formOptions#${formId}`);
		}
		else
		{
			this.cache.keys()
				.filter((key) => {
					return key.startsWith('formOptions#');
				})
				.forEach((key) => {
					this.cache.delete(key);
				});
		}
	}

	check(options): Promise<any>
	{
		return request({action: 'check', data: {options}});
	}
}