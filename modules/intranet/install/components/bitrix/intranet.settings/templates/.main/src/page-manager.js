import {ajax} from 'main.core';
import type {BaseSettingsPage} from "./pages/base-settings-page";

export class PageManager
{
	#pages: Array<BaseSettingsPage>


	constructor(pages: Array<BaseSettingsPage>)
	{
		this.#pages = pages;
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