import { CategoryModel } from 'crm.category-model';
import { ajax as Ajax, Extension } from 'main.core';
import type { SettingsCollection } from 'main.core.collections';

let instance = null;

export class CategoryRepository
{
	#extensionSettings: SettingsCollection = Extension.getSettings('crm.conversion');

	#storage: Map<number, CategoryModel[]> = new Map();

	static get Instance(): CategoryRepository
	{
		if (instance === null)
		{
			instance = new CategoryRepository();
		}

		return instance;
	}

	isCategoriesEnabled(entityTypeId: number): boolean
	{
		return Boolean(this.#extensionSettings.get(`isCategoriesEnabled.${entityTypeId}`, false));
	}

	getCategories(entityTypeId: number): Promise<CategoryModel[]>
	{
		if (this.#storage.has(entityTypeId))
		{
			return Promise.resolve(this.#storage.get(entityTypeId));
		}

		return Ajax.runAction('crm.conversion.getDstCategoryList', {
			data: {
				entityTypeId,
			},
		}).then(({ data }) => {
			const models = [];

			data?.categories?.forEach((categoryData) => {
				models.push(new CategoryModel(categoryData));
			});

			this.#storage.set(entityTypeId, models);

			return models;
		});
	}
}
