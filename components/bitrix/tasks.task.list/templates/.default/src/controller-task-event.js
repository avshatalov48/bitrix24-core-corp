import {EventEmitter} from "main.core.events";
import {Type} from "main.core";

import {Action} from "./controller-task-action";


class ControllerTaskEvent
{
	#timeout = 0;

	constructor(options = {})
	{
		options = Type.isPlainObject(options) ? options : {};

		this.#timeout = options.timeout ?? 100;
	}

	resolveIdByParam(cmd, params)
	{
		if (this.#isDefinedAction(cmd))
		{
			const inx = this.#resolveIndexByEvent(cmd, params)
			if (inx > 0 )
			{
				return inx;
			}
			else
			{
				throw new Error("Index is not resolved for command: " + cmd);
			}
		}
		else
		{
			// console.log('cmd is undefined', cmd);
		}
	}

	#resolveIndexByEvent(cmd, params)
	{
		let result = '';

		if ([Action.TASK_ADD, Action.TASK_UPDATE, Action.TASK_REMOVE, Action.TASK_VIEW, Action.USER_OPTION_CHANGED].includes(cmd))
		{
			result = params.TASK_ID
		}
		else
		{
			result = params.taskId;
		}

		return result;
	}

	#isDefinedAction(value)
	{
		const types = Object.values(Action);
		return types.includes(value);
	}

	intervalEmitByParams(params)
	{
		return new Promise((resolve, reject) =>
		{
			const items = Object.values(params);

			const tm = setInterval(() => {
				if (items.length === 0)
				{
					clearTimeout(tm);
					resolve();
					return;
				}
				const item = items.shift();
				const {poolItem, repository} = item;

				// console.log('poolItemsShift', items);
				this.#emit(poolItem, repository);

			}, this.#timeout);
		})
	}

	batchEmitByParams(params)
	{
		return new Promise((resolve, reject) =>
		{
			const items = Object.values(params);
			// console.log('poolItemsShift', items);
			for (let inx in items)
			{
				if (!items.hasOwnProperty(inx))
				{
					continue;
				}
				const item = items[inx];
				const {poolItem, repository} = item;

				this.#emit(poolItem, repository);
			}

			resolve();
		})
	}

	#emit(item, repository)
	{
		let [param] = Object.values(item);
		let [command] = Object.keys(item);

		let params = param.fields.params;
		// console.log('repository', repository);
		EventEmitter.emit('BX.Tasks.Event:onEmit', {command, params, repository});
	}
}

export
{
	ControllerTaskEvent
}