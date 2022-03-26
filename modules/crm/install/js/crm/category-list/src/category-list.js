import { ajax as Ajax, Reflection } from "main.core";
import { CategoryModel } from "crm.category-model";

let instance = null;

/**
 * @memberOf BX.Crm
 */
export class CategoryList {

	#items: Object<number, CategoryModel[]> = {};
	#isProgress: boolean = false;

	static get Instance(): CategoryList
	{
		if ((window.top !== window) && Reflection.getClass('top.BX.Crm.CategoryList'))
		{
			return window.top.BX.Crm.CategoryList.Instance;
		}

		if (instance === null)
		{
			instance = new CategoryList();
		}

		return instance;
	}

	getItems(entityTypeId: number): Promise<CategoryModel[]>
	{
		return new Promise((resolve, reject) => {
			if (this.#items.hasOwnProperty(entityTypeId))
			{
				resolve(this.#items[entityTypeId]);
				return;
			}

			this.#loadItems(entityTypeId).then((categories) => {
				this.#items[entityTypeId] = categories;
				resolve(categories);
			}).catch((error) => {
				this.#items[entityTypeId] = [];
				reject(error);
			});
		});
	}

	setItems(entityTypeId: number, items: CategoryModel[]): CategoryList
	{
		this.#items[entityTypeId] = items;

		return this;
	}

	#loadItems(entityTypeId): Promise<CategoryModel[], string>
	{
		return new Promise((resolve, reject) => {
			if (this.#isProgress)
			{
				reject('CategoryList is already loading');
				return;
			}
			this.#isProgress = true;
			Ajax.runAction('crm.category.list', {
				data: {
					entityTypeId,
				}
			}).then((response) => {
				this.#isProgress = false;
				const categories = [];
				response.data.categories.forEach((category) => {
					categories.push(new CategoryModel(category));
				});
				resolve(categories);
			}).catch((response) => {
				this.#isProgress = false;
				reject("CategoryList error: " + response.errors.map(({message}) => message).join("; "));
			});
		});
	}
}
