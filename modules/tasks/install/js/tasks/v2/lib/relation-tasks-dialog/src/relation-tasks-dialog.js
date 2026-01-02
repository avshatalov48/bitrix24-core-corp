import { Event, Tag } from 'main.core';
import { EventEmitter } from 'main.core.events';

import { EntitySelectorEntity, EventName, TaskField } from 'tasks.v2.const';
import { EntitySelectorDialog, type ItemId } from 'tasks.v2.lib.entity-selector-dialog';
import { relationError } from 'tasks.v2.lib.relation-error';
import { taskService } from 'tasks.v2.provider.service.task-service';
import type { TaskModel } from 'tasks.v2.model.tasks';

import { RelationMeta } from './types';

export class RelationTasksDialog
{
	#meta: RelationMeta;
	#taskId: number | string;
	#dialog: EntitySelectorDialog | null = null;
	#onUpdateOnce: Function | null = null;

	constructor(meta: RelationMeta)
	{
		this.#meta = meta;
	}

	setTaskId(taskId: number | string): RelationTasksDialog
	{
		this.#taskId = taskId;

		return this;
	}

	showTo(targetNode: HTMLElement, targetContainer: HTMLElement): void
	{
		this.#dialog ??= this.#createDialog();
		if (this.#dialog.isLoaded())
		{
			this.#addTaskItems(this.#task[this.#meta.idsField]);
		}
		this.#dialog.selectItemsByIds(this.#items);
		this.#dialog.setSelectableByIds(this.#selectableItems);
		this.#dialog.getPopup().setTargetContainer(targetContainer);
		this.#dialog.showTo(targetNode);
	}

	getDialog(): EntitySelectorDialog
	{
		return this.#dialog;
	}

	onUpdateOnce(callback: Function): RelationTasksDialog
	{
		this.#onUpdateOnce = callback;

		return this;
	}

	#createDialog(): EntitySelectorDialog
	{
		const handleClose = (): void => {
			if (!this.#dialog.isLoaded())
			{
				return;
			}

			const ids = this.#dialog.getSelectedItems().map((item) => Number(item.getId()));
			if (ids.sort().join(',') !== this.#task[this.#meta.idsField].sort().join(','))
			{
				void this.#updateTask();
			}
		};

		return new EntitySelectorDialog({
			context: 'tasks-card',
			multiple: true,
			enableSearch: true,
			width: 500,
			entities: [
				{
					id: EntitySelectorEntity.Task,
				},
			],
			preselectedItems: this.#items,
			popupOptions: {
				events: {
					onClose: handleClose,
				},
			},
			footer: this.#createFooter(),
		});
	}

	#createFooter(): HTMLElement
	{
		const footer = Tag.render`
			<span class="ui-selector-footer-link ui-selector-footer-link-add">${this.#meta.footerText}</span>
		`;

		Event.bind(footer, 'click', () => EventEmitter.emit(EventName.OpenCompactCard, {
			[TaskField.Title]: this.getDialog().getActiveTab().getLastSearchQuery?.().getQuery(),
			[this.#meta.relationToField]: this.#taskId,
		}));

		return footer;
	}

	#addTaskItems(ids: number): void
	{
		const itemIds = new Set(this.#dialog.getItems().map((it) => it.getId()));
		ids.filter((id) => !itemIds.has(id)).forEach((id: number) => {
			const task = taskService.getStoreTask(id);
			this.#dialog.addItem({
				id,
				entityId: EntitySelectorEntity.Task,
				title: task.title,
				selected: true,
				sort: 0,
				tabs: ['recents'],
			});
		});
	}

	async #updateTask(): void
	{
		const selectedItems = this.#dialog.getSelectedItems();
		const newTaskIds = selectedItems.map((item) => Number(item.getId()));
		const currentTaskIds = this.#task[this.#meta.idsField];

		const idsToDelete = currentTaskIds.filter((id) => !newTaskIds.includes(id));
		const idsToAdd = newTaskIds.filter((id) => !currentTaskIds.includes(id));

		await Promise.all([
			this.#meta.service.delete(this.#taskId, idsToDelete),
			this.#add(idsToAdd),
		]);

		this.#onUpdateOnce?.();
		this.#onUpdateOnce = null;
	}

	async #add(ids: number[]): Promise<void>
	{
		const error = await this.#meta.service.add(this.#taskId, ids);

		if (error)
		{
			void relationError.setTaskId(this.#taskId).showError(error, this.#meta.id);
		}
	}

	get #selectableItems(): { selectable: ItemId[], unselectable: ItemId[] }
	{
		const selectableIds = [];
		const unselectableIds = [];

		this.#task[this.#meta.idsField].forEach((id: number) => {
			const task = taskService.getStoreTask(id);
			if (!task || task.rights.detachParent || task.rights.detachRelated)
			{
				selectableIds.push(id);
			}
			else
			{
				unselectableIds.push(id);
			}
		});

		return {
			selectable: this.#mapIdsToItemIds(selectableIds),
			unselectable: this.#mapIdsToItemIds(unselectableIds),
		};
	}

	get #items(): ItemId[]
	{
		return this.#mapIdsToItemIds(this.#task[this.#meta.idsField]);
	}

	#mapIdsToItemIds(ids: number[]): ItemId[]
	{
		return ids.map((id: number) => [EntitySelectorEntity.Task, id]);
	}

	get #task(): TaskModel
	{
		return taskService.getStoreTask(this.#taskId);
	}
}
