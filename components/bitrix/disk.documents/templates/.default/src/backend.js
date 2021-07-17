import {ajax, Runtime} from 'main.core';

class BackendInner {
	static idsForShared = { }
	static idsForExternalLinks = { }
	static sendForInfo = Runtime.debounce(
		function()
		{
			const requestData = {
				'shared': BackendInner.idsForShared,
				'externalLink': BackendInner.idsForExternalLinks
			}
			BackendInner.idsForShared = {};
			BackendInner.idsForExternalLinks = {};

			const request = {};
			for (let action in requestData)
			{
				if (requestData.hasOwnProperty(action))
				{
					for (let id in requestData[action])
					{
						if (requestData[action].hasOwnProperty(id))
						{
							request[id] = (request[id] || []);
							request[id].push(action);
						}
					}
				}
			}

			ajax
				.runComponentAction(
					Backend.component,
					'getInfo',
					{
						mode: 'ajax',
						data: {
							trackedObjectIds: request
						},
					}
				)
				.then(({data}) => {
					for (let action in requestData)
					{
						if (requestData.hasOwnProperty(action))
						{
							for (let id in requestData[action])
							{
								if (requestData[action].hasOwnProperty(id))
								{
									requestData[action][id][0]({data: data[id][action]});
								}
							}
						}
					}
				})
				.catch(({errors}) => {
					for (let action in requestData)
					{
						if (requestData.hasOwnProperty(action))
						{
							for (let id in requestData[action])
							{
								if (requestData[action].hasOwnProperty(id))
								{
									requestData[action][id][1]({errors});
								}
							}
						}
					}
				});
		}, 500
	)
}

export default class Backend
{
	static component: string = 'bitrix:disk.documents';

	static getShared(id)
	{
		return new Promise((resolve, reject) => {
			BackendInner.idsForShared[id] = [resolve, reject];
			BackendInner.sendForInfo();
		});
	}

	static getExternalLink(id)
	{
		return new Promise((resolve, reject) => {
			BackendInner.idsForExternalLinks[id] = [resolve, reject];
			BackendInner.sendForInfo();
		});
	}

	static getMenuActions(id)
	{
		return ajax
			.runComponentAction(
				Backend.component,
				'getMenuActions',
				{
					mode: 'ajax',
					data: {
						trackedObjectId: id
					},
					analyticsLabel: Backend.component + '.gridMenuActions',
				}
			);
	}

	static getMenuOpenAction(id)
	{
		return ajax
			.runComponentAction(
				Backend.component,
				'getMenuOpenAction',
				{
					mode: 'ajax',
					data: {
						trackedObjectId: id
					},
					analyticsLabel: Backend.component + '.gridMenuOpenAction',
				}
			);
	}

	static renameAction(id, newName)
	{
		return ajax
			.runAction('disk.api.trackedObject.rename', {
				data: {
					objectId: id,
					newName: newName
				}
			});
	}
}