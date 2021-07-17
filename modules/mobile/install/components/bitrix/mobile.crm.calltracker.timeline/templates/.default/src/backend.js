import {ajax} from 'main.core';
import Configuration from './configuration';

export default class Backend
{
	static request({action, data}): Promise<RequestResponse>
	{
		return ajax
			.runComponentAction(
				Configuration.componentName,
				action,
				{
					mode: 'class',
					data: data,
					signedParameters: Configuration.signedParameters
				},
			);
	}

	static getItem(id, options): Promise<RequestResponse>
	{
		return Backend.request({
			action: 'getItem',
			data: {id, options},
		});
	}
	static createItem({text, files})
	{
		return Backend.request({
			action: 'createItem',
			data: {text, files},
		});
	}

	static getItemsFromPage(itemId: number, pageNumber: number)
	{
		return new Promise((resolve, reject) => {
				ajax.runComponentAction(
					Configuration.componentName,
					'getItems',
					{
						mode: 'class',
						data: {
							itemId: itemId
						},
						navigation: {
							page: pageNumber
						},
						signedParameters: Configuration.signedParameters
					},
				)
				.then(resolve, reject);
		});
	}
}