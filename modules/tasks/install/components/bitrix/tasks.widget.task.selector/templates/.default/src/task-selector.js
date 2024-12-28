import { TagSelector } from 'ui.entity-selector';
import {EventEmitter} from "main.core.events";

export class TaskSelector extends EventEmitter
{
	#multiple = null;
	#currentTasks = null;
	#userId = null;
	#tagSelector = null;
	#textBoxWidth = 205;

	constructor(data)
	{
		super(data);
		this.setEventNamespace('BX.Tasks.TaskSelector');

		this.#multiple = data.multiple;
		this.#currentTasks = JSON.parse(data.currentTasks);
		this.#userId = data.userId;
	};

	getSelector()
	{
		if (!this.#tagSelector)
		{
			if(!Array.isArray(this.#currentTasks))
			{
				this.#currentTasks = [];
			}

			this.#tagSelector = new TagSelector({
				textBoxWidth: this.#textBoxWidth,
				multiple: this.#multiple,
				items: this.#getItems(),
				dialogOptions: {
					showAvatars: false,
					enableSearch: true,
					context: 'TASKS',
					entities: [
						{
							id: 'task-with-id',
							itemOptions:
								{
									default: {
										link: this.#getLinkByTaskId('#id#'),
									}
								},
						},
					],
				},
				events: {
					onTagAdd: (event) => this.#onTagAdd(event),
					onTagRemove: (event) => this.#onTagRemove(event),
				},
			});
		}

		return this.#tagSelector;
	};

	#getLinkByTaskId(taskId)
	{
		return '/company/personal/user/' + this.#userId + '/tasks/task/view/' + taskId + '/';
	};

	#getItems()
	{
		const items = [];
		this.#currentTasks.forEach((task) => {
			items.push({
				id: task.ID,
				entityId: 'task-with-id',
				title: task.TITLE + '[' + task.ID + ']',
				link: this.#getLinkByTaskId(task.ID),
			});
		});

		return items;
	};

	#onTagAdd(event)
	{
		const data = {
			'selector': event.getTarget(),
			'tag': event.getData(),
		};

		this.emit('tagAdded', data);
	};

	#onTagRemove(event)
	{
		const data = {
			'selector': event.getTarget(),
			'tag': event.getData(),
		};

		this.emit('tagRemoved', data);
	}
}
