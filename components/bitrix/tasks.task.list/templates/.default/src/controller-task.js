import {EventEmitter} from "main.core.events";

import {ControllerTaskEvent} from "./controller-task-event"
import {ControllerTaskRepository} from "./controller-task-repository"

class ControllerTask
{
	static getRepositoryByCollectionPushEventsAsync(items, context)
	{
		const taskRepository = new ControllerTaskRepository();

		const id = ControllerTask.getValuesId(items);

		return new Promise((resolve, reject) =>
		{
			taskRepository.callByFilter(
				{
					id,
					returnAccess: 'Y',
					siftThroughFilter: {
						userId: context.ownerId,
						groupId: context.groupId
					}
				},
				{
					arParams: context.arParams
				}
			)
				.then(() =>
				{
					const repository = taskRepository.get();

					EventEmitter.emit('BX.Tasks.ControllerTask:onGetRepository', {
						params: { items, repository }
					});

					resolve();
				})
		})
	}

	static emitByCollectionEventImitterEventsAsync(items)
	{
		const eventLib = new ControllerTaskEvent();

		const params = ControllerTask.prepareByPoolToEmit(items);

		return new Promise((resolve, reject) =>
		{
			eventLib.batchEmitByParams(params)
				.then(() => resolve())
		})
	}

	static getValuesId(collection): ?Array
	{
		const result = [];

		try
		{
			for (let inx in collection)
			{
				if (!collection.hasOwnProperty(inx))
				{
					continue;
				}

				let [cmd] = Object.keys(collection[inx]);
				let [params] = Object.values(collection[inx]);

				let id = params.fields.id;
				if (result.includes(id) === false)
				{
					result.push(id)
				}
			}
		}
		catch (e) {}

		return result;
	}

	static prepareByPoolToEmit(items)
	{
		const result = [];

		Object.keys(items).forEach((key) => {
			const item = items[key];

			const poolItems = item['default'].fields.params.items;
			const repository = item['default'].fields.params.repository;

			Object.keys(poolItems).forEach((key) => {
				const poolItem = poolItems[key];

				result.push({poolItem, repository})
			})
		})
		// console.log('collection', result);
		return result;
	}
}

export
{
	ControllerTask
}