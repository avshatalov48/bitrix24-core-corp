import {ajax} from 'main.core';
import type {BaseSettingsPage} from "./pages/base-settings-page";

export class PageManager
{
	#pages: Array<BaseSettingsPage>


	constructor(pages: Array<BaseSettingsPage>)
	{
		this.#pages = pages;
	}

	fetchUnfetchedPages(): Promise
	{
		const pages = [];
		this.#pages.forEach((page: BaseSettingsPage) => {
			if (!page.hasData())
			{
				pages.push(page);
			}
		});
		if (pages.length <= 0)
		{
			return Promise.resolve();
		}

		return new Promise((resolve, reject) => {
			ajax.runComponentAction(
				'bitrix:intranet.settings',
				'getSome',
				{
					mode: 'class',
					data: { types: pages.map((page: BaseSettingsPage) => page.getType()) }
				},
			).then((response) => {
				const data = response.data ?? {};
				pages.forEach((page: BaseSettingsPage) => {
					if (data[page.getType()])
					{
						page.setData(data[page.getType()]);
					}
				});
				resolve();
			}, reject)
		});
	}

	fetchPage(page: BaseSettingsPage): Promise
	{
		return new Promise((resolve, reject) => {
			const pageIsFound = this.#pages.some((savedPage: BaseSettingsPage) => {
				if (page.getType() === savedPage.getType())
				{
					ajax.runComponentAction(
						'bitrix:intranet.settings',
						'get',
						{
							mode: 'class',
							data: {
								type: page.getType(),
							},
						},
					).then(resolve, reject);
					return true;
				}
				return false;
			});
			if (pageIsFound !== true)
			{
				return reject({error: 'The page was not found in pageManager'});
			}
		});
	}

	collectData()
	{
		const data = {};
		this.#pages.forEach((page: BaseSettingsPage) => {
			if (page.hasData())
			{
				data[page.getType()] = this.constructor.getFormData(page.getFormNode());
			}
		});

		return data;
	}

	static getFormData(formNode: HTMLElement): ?Object
	{
		return BX.ajax.prepareForm(formNode).data;
	}
}