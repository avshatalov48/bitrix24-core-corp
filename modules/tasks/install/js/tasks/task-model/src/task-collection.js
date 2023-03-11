import {ajax, Text, Type} from "main.core";
import {TaskModel} from "tasks.task-model";
import {EventEmitter} from "main.core.events";

export class TaskCollection
{
	#map: Map = new Map();

	init(map: {})
	{
		this.clear();

		map.forEach((item, index) =>
		{
			if (item['id'] > 0)
			{
				let model = new TaskModel({
					fields : {
						id : Text.toNumber(item.id),
						title: item.title.toString(),
					},
				});

				this.#map.set(
					Text.toNumber(index),
					model
				)
			}
		});
	}

	refreshByFilter(fields: {}): Promise
	{
		return new Promise((resolve, reject) =>
		{
			const cmd = 'tasks.task.list';

			let {filter, params} = TaskCollection.internalize(fields);

			if(Object.keys(filter).length <= 0)
			{
				return Promise.reject({
					status: 'error',
					errors: [
						'filter is not set'
					],
				});
			}

			ajax.runAction(
				cmd,
				{
					data: {
						filter,
						params,
						start: -1,
					},
				},
			)
			.then((result) =>
			{
				let tasks = result.data.tasks ?? null;

				if (Type.isArrayFilled(tasks))
				{
					this.init(tasks);
					this.#onChangeData();
				}

				resolve()
			})
			.catch(reject)
		});
	}

	#onChangeData()
	{
		EventEmitter.emit(this,'BX.Tasks.TaskModel.Collection:onChangeData');
	}

	static internalize(fields: {})
	{
		const result = {
			filter: {},
			params: {},
		};

		try
		{
			for (let name in fields)
			{
				if (!fields.hasOwnProperty(name))
				{
					continue;
				}

				switch (name)
				{
					case 'id':
						result.filter.ID = fields[name];
						break;
					case 'returnAccess':
						result.params.RETURN_ACCESS = fields[name];
						break;
					case 'siftThroughFilter':
						result.params.SIFT_THROUGH_FILTER = TaskCollection.internalizeSiftThroughFilter(fields[name]);
						break;
				}
			}
		}
		catch (e) {}

		return result;
	}

	static internalizeSiftThroughFilter(fields: {}): {}
	{
		const result = {};

		try
		{
			for (let name in fields)
			{
				if (!fields.hasOwnProperty(name))
				{
					continue;
				}

				switch (name)
				{
					case 'userId':
						result.userId = fields[name];
						break;
					case 'groupId':
						result.groupId = fields[name];
						break;
				}
			}
		}
		catch (e) {}

		return result;
	}

	getById(id): ?TaskModel
	{
		for (let model of this.#map.values())
		{
			if (model.getId() === Text.toNumber(id))
			{
				return model
			}
		}
	}

	getFieldsById(id): any
	{
		return this.getById(id)?.getFields() || 0;
	}

	getByIndex(index): ?TaskModel
	{
		return this.#map.get(Text.toNumber(index))
	}

	getFieldsByIndex(index): any
	{
		return this.getByIndex(index)?.getFields() || 0;
	}

	count()
	{
		return this.#map.size;
	}

	clear(): TaskCollection
	{
		this.#map.clear();

		return this;
	}
}