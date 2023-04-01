import {Cache, ajax, Type} from 'main.core';

const {MemoryCache} = Cache;

export class Backend
{
	static #instance = null;
	#cache = new MemoryCache();

	static getInstance(): Backend
	{
		if (!Backend.#instance)
		{
			Backend.#instance = new Backend();
		}

		return Backend.#instance;
	}

	getFieldsList(presetId: number): Promise<any>
	{
		return this.#cache.remember('fieldsList', () => {
			return new Promise((resolve, reject) => {
				ajax
					.runAction('crm.api.form.field.list', {json: {presetId}})
					.then((result) => {
						if (Type.isPlainObject(result?.data?.tree))
						{
							resolve(result.data.tree);
						}
						else
						{
							reject(result);
						}
					})
					.catch((error) => {
						reject(error);
					});
			});
		});
	}

	getFieldsSet(id: number): Promise<any>
	{
		return new Promise((resolve, reject) => {
			ajax
				.runAction('crm.api.fieldset.get', {json: {id}})
				.then((result) => {
					if (Type.isPlainObject(result?.data))
					{
						resolve(result.data);
					}
					else
					{
						reject(result);
					}
				})
				.catch((error) => {
					reject(error);
				});
		});
	}

	saveFieldsSet(options: {[key: string]: any}): Promise<any>
	{
		return new Promise((resolve, reject) => {
			ajax
				.runAction('crm.api.fieldset.set', {json: {options}})
				.then((result) => {
					if (Type.isPlainObject(result?.data))
					{
						resolve(result);
					}
					else
					{
						reject(result);
					}
				})
				.catch((error) => {
					reject(error);
				});
		});
	}
}