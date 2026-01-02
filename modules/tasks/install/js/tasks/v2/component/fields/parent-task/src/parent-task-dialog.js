import { Runtime } from 'main.core';

import { Core } from 'tasks.v2.core';
import { EntitySelectorEntity, Model, TaskField } from 'tasks.v2.const';
import { relationError } from 'tasks.v2.lib.relation-error';
import { subTasksService } from 'tasks.v2.provider.service.relation-service';
import { EntitySelectorDialog } from 'tasks.v2.lib.entity-selector-dialog';
import type { ItemId } from 'tasks.v2.lib.entity-selector-dialog';
import type { TaskModel } from 'tasks.v2.model.tasks';

class ParentTaskDialog
{
	#taskId: number | string;
	#dialog: EntitySelectorDialog;
	#onUpdateOnce: Function | null = null;

	setTaskId(taskId: number | string): ParentTaskDialog
	{
		this.#taskId = taskId;

		return this;
	}

	showTo(targetNode: HTMLElement): void
	{
		this.#dialog ??= this.#createDialog();
		this.#dialog.selectItemsByIds(this.#items);
		this.#dialog.showTo(targetNode);
	}

	onUpdateOnce(callback: Function): void
	{
		this.#onUpdateOnce = callback;
	}

	#createDialog(): EntitySelectorDialog
	{
		const onItemChangeDebounced = Runtime.debounce(this.#onItemChange, 10, this);

		return new EntitySelectorDialog({
			context: 'tasks-card',
			multiple: false,
			enableSearch: true,
			width: 500,
			entities: [
				{
					id: EntitySelectorEntity.Task,
				},
			],
			preselectedItems: this.#items,
			events: {
				'Item:onSelect': onItemChangeDebounced,
				'Item:onDeselect': onItemChangeDebounced,
				onHide: this.#clearOnUpdateOnce,
				onDestroy: this.#clearOnUpdateOnce,
			},
		});
	}

	async #onItemChange(): void
	{
		const selectedTaskId = this.#dialog.getSelectedItems()[0]?.getId() ?? 0;

		if (selectedTaskId > 0)
		{
			const error = await subTasksService.setParent(this.#taskId, selectedTaskId);

			if (error)
			{
				void relationError.setTaskId(this.#taskId).showError(error, TaskField.Parent);

				return;
			}
		}
		else if (selectedTaskId === 0 && this.#task.parentId > 0)
		{
			await subTasksService.delete(this.#task.parentId, [this.#taskId]);
		}

		this.#onUpdateOnce?.();
		this.#clearOnUpdateOnce();
	}

	#clearOnUpdateOnce = (): void => {
		this.#onUpdateOnce = null;
	};

	get #items(): ItemId[]
	{
		return this.#task.parentId ? [[EntitySelectorEntity.Task, this.#task.parentId]] : [];
	}

	get #task(): TaskModel
	{
		return Core.getStore().getters[`${Model.Tasks}/getById`](this.#taskId);
	}
}

export const parentTaskDialog = new ParentTaskDialog();
