import {EventEmitter} from "main.core.events";

import {ControllerTaskEvent} from "./controller-task-event"
import {ControllerTaskRepository} from "./controller-task-repository"
import {Action} from "./controller-task-action";

class ControllerTask
{
	static getRepository(items, context): Promise
	{
		const taskRepository = new ControllerTaskRepository();

		const taskIds = [];
		items.forEach((item) => {
			taskIds.push(item.data.id);
		});

		return new Promise((resolve, reject) => {
			// eslint-disable-next-line promise/catch-or-return
			taskRepository.callByFilter(
				{
					id: taskIds,
					returnAccess: 'Y',
					siftThroughFilter: {
						userId: context.ownerId,
						groupId: context.groupId,
					},
				},
				{
					arParams: context.arParams,
					navigation: {
						pageNumber: context.getPageNumber(),
						pageSize: context.getPageSize(),
					},
				},
			)
				.then(() => {
					resolve(taskRepository.get());
				});
		});
	}

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
					arParams: context.arParams,
					navigation: {
						pageNumber: context.getPageNumber(),
						pageSize: context.getPageSize(),
					},
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

	static getItemsByType(poolItems, type)
	{
		const result = [];

		Object.keys(poolItems).forEach((key) => {
			const poolItem = poolItems[key];

			if (Object.keys(poolItem)[0] === type)
			{
				result.push(poolItem[type].fields)
			}
		})

		return result;
	}

	static sortedById(items)
	{
		items.sort((l,r) => l.id > r.id
			? 1
			: (r.id < r.id
				? -1
				: 0)
		);
		
		return items;
	}

	static prepareByPoolToEmit(items)
	{
		const result = [];
		const action = Action.USER_OPTION_CHANGED;

		Object.keys(items).forEach((key) => {
			const item = items[key];

			const poolItems = item['default'].fields.params.items;
			const repository = item['default'].fields.params.repository;

			let poolItem = {};
			const sortedItems = ControllerTask.sortedById(
				ControllerTask.getItemsByType(poolItems, action));

			Object.keys(poolItems).forEach((key) => {

				if (Object.keys(poolItems[key])[0] === action)
				{
					// ORDER actions user_option_changed BY ASC
					poolItem = {[action]: {fields: sortedItems.shift()}}
				}
				else
				{
					poolItem = poolItems[key];
				}

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