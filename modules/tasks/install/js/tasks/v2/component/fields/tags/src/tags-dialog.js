import type { BaseEvent } from 'main.core.events';
import type { Store } from 'ui.vue3.vuex';
import type { Item } from 'ui.entity-selector';

import { Footer } from 'tasks.entity-selector';
import { EntitySelectorDialog, type ItemId } from 'tasks.v2.lib.entity-selector-dialog';
import { Core } from 'tasks.v2.core';
import { EntitySelectorEntity, Model } from 'tasks.v2.const';
import { taskService } from 'tasks.v2.provider.service.task-service';
import type { TaskModel } from 'tasks.v2.model.tasks';

const dialogs: { [key: string]: EntitySelectorDialog } = {};

class TagsDialog
{
	#taskId: number | string;
	#onCloseOnce: Function | null = null;
	#onUpdateOnce: Function | null = null;

	setTaskId(taskId: number | string): TagsDialog
	{
		this.#taskId = taskId;

		return this;
	}

	onCloseOnce(callback: Function): TagsDialog
	{
		this.#onCloseOnce = callback;

		return this;
	}

	onUpdateOnce(callback: Function): void
	{
		this.#onUpdateOnce = callback;
	}

	showTo(targetNode: HTMLElement): void
	{
		const dialog = this.#getDialog(this.#taskId);
		if (dialog.isLoaded() && !dialog.isOpen())
		{
			dialog.selectItemsByIds(this.#getItems(this.#taskId));
		}
		dialog.showTo(targetNode);
	}

	updateItems(): void
	{
		const dialog = this.#getDialog(this.#taskId);
		if (dialog.isLoaded() && dialog.isOpen())
		{
			dialog.selectItemsByIds(this.#getItems(this.#taskId));
		}
	}

	#getItems(taskId: number | string): ItemId[]
	{
		const tags = new Set(this.#getTask(taskId).tags);

		return this.#getDialog(taskId).getItems()
			.filter((item: Item) => tags.has(item.getTitle()))
			.map((item: Item) => [EntitySelectorEntity.Tag, item.getId()])
		;
	}

	#getDialog(taskId: number | string): EntitySelectorDialog
	{
		const userId = this.#currentUserId;
		const groupId = this.#getTask(taskId).groupId ?? 0;

		const key = `${taskId}-${groupId}`;
		if (dialogs[key])
		{
			return dialogs[key];
		}

		let changed = false;
		const handleChanged = (): void => {
			changed = true;
		};

		const handleClose = (): void => {
			if (changed)
			{
				void this.#updateTask(taskId);

				changed = false;
			}

			this.#onCloseOnce?.();
			this.#onCloseOnce = null;
		};

		dialogs[key] = new EntitySelectorDialog({
			multiple: true,
			enableSearch: true,
			dropdownMode: true,
			compactView: true,
			entities: [
				{
					id: EntitySelectorEntity.Tag,
					options: { taskId, groupId },
				},
			],
			searchOptions: {
				allowCreateItem: true,
			},
			footer: Footer,
			footerOptions: { userId, groupId },
			clearUnavailableItems: true,
			events: {
				'Item:onSelect': handleChanged,
				'Item:onDeselect': handleChanged,
				'Search:onItemCreateAsync': (event: BaseEvent): void => {
					const tag = event.getData().searchQuery.getQuery();
					if (this.#getTask(taskId).tags.includes(tag))
					{
						return;
					}

					dialogs[key].addItem({
						id: tag,
						entityId: EntitySelectorEntity.Tag,
						title: tag,
						tabs: 'all',
						selected: true,
					});

					changed = true;
				},
			},
			popupOptions: {
				events: {
					onClose: handleClose,
				},
			},
		});

		return dialogs[key];
	}

	async #updateTask(taskId: number | string): Promise<void>
	{
		const tags = this.#getDialog(taskId).getSelectedItems().map((item: Item) => item.getTitle());

		void taskService.update(taskId, { tags });

		this.#onUpdateOnce?.(tags);
		this.#onUpdateOnce = null;
	}

	#getTask(id: number | string): TaskModel
	{
		return this.$store.getters[`${Model.Tasks}/getById`](id);
	}

	get #currentUserId(): number
	{
		return this.$store.getters[`${Model.Interface}/currentUserId`];
	}

	get $store(): Store
	{
		return Core.getStore();
	}
}

export const tagsDialog = new TagsDialog();
