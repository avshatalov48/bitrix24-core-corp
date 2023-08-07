import {Text, Type} from "main.core";
import {TaskCollection} from "tasks.task-model";

class ControllerTaskRepository
{
	#repository = {
		collectionNear: new Map(),
		collectionGrid: new Map(),
		collection: new TaskCollection(),
		collectionSiftThroughFilter: new TaskCollection(),
	};

	#internalize(fields, params)
	{
		const result = {};

		const items = {
			collectionSiftThroughFilter: {
				cmd: 'tasks.task.list',
				filter: {
					id: fields.id,
					returnAccess: fields.returnAccess,
					siftThroughFilter: {
						userId: fields.siftThroughFilter.userId,
						groupId: fields.siftThroughFilter.groupId,
					}
				}
			},
			collection: {
				cmd: 'tasks.task.list',
				filter: {
					id: fields.id,
					returnAccess: fields.returnAccess,
				}
			},
			collectionGrid: {
				cmd: 'tasks.task.getGridRows',
				filter: {
					id: fields.id,
				},
				params: params.arParams
			},
			collectionNear: {
				cmd: 'tasks.task.getNearTasks',
				filter: {
					id: fields.id,
				},
				navigation: params.navigation,
				params: params.arParams,
			}
		};

		Object.keys(items).forEach((type) => {
			const item = items[type];
			switch (type)
			{
				case 'collection':
				case 'collectionSiftThroughFilter':
					result[type] = {
						cmd: item.cmd,
						param: TaskCollection.internalize(item.filter),
					}
					break;
				case 'collectionGrid':
				case 'collectionNear':
					result[type] = {
						cmd: item.cmd,
						param: {
							taskIds: Type.isArrayFilled(item.filter.id) ? item.filter.id : [item.filter.id],
							navigation: Type.isUndefined(item?.navigation) ? null : item.navigation,
							arParams: item.params
						},
					}
					break;
			}
		});

		return result;
	}

	#buildQuery(items)
	{
		const result = {};

		Object.keys(items).forEach((inx) => {
			const item = items[inx];
			result[inx] = [item.cmd, item.param];
		});

		return result;
	}

	#init(type, items)
	{
		if (Object.keys(this.#repository).includes(type))
		{
			switch (type)
			{
				case 'collection':
				case 'collectionSiftThroughFilter':
					this.#repository[type].init(items.tasks)
					break;
				case 'collectionNear':
				case 'collectionGrid':
					Object.keys(items).forEach((id) =>
					{
						if (id > 0)
						{
							const row = {};
							row[id] = items[id];

							this.#repository[type].set(
								Text.toNumber(id),
								row
							)
						}
					});
					break;
			}
		}
	}

	callByFilter(fields, params): Promise
	{
		return new Promise((resolve, reject) =>
		{
			const items = this.#internalize(fields, params);
			const sets = this.#buildQuery(items);

			if(Object.keys(sets).length > 0)
			{
				BX.rest.callBatch(
					sets,
					(result) => {
						Object.keys(sets).forEach((type) =>
						{
							let set = result[type] ?? null;
							if (Type.isNull(set) === false)
							{
								let items = set.answer.result
								this.#init(type, items);
							}
						})

						resolve(this.get());
					}
				);
			}
		});
	};

	get()
	{
		return this.#repository;
	}
}

export
{
	ControllerTaskRepository
}