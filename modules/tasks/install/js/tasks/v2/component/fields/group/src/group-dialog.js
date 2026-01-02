import { Runtime } from 'main.core';
import type { Item } from 'ui.entity-selector';

import { EntitySelectorDialog, type ItemId } from 'tasks.v2.lib.entity-selector-dialog';
import { Core } from 'tasks.v2.core';
import { EntitySelectorEntity, Model } from 'tasks.v2.const';
import { taskService } from 'tasks.v2.provider.service.task-service';
import type { TaskModel } from 'tasks.v2.model.tasks';
import type { GroupModel } from 'tasks.v2.model.groups';

class GroupDialog
{
	#taskId: number | string;
	#dialog: EntitySelectorDialog;
	#onUpdateOnce: Function | null = null;
	#onUpdate: Function | null = null;
	#onHide: Function | null = null;

	setTaskId(taskId: number | string): GroupDialog
	{
		this.#taskId = taskId;

		return this;
	}

	init(): void
	{
		this.#dialog ??= this.#createDialog();
	}

	showTo(targetNode: HTMLElement): void
	{
		this.init();
		this.#dialog.selectItemsByIds(this.#items);
		this.#dialog.showTo(targetNode);
	}

	onUpdateOnce(callback: Function): void
	{
		this.#onUpdateOnce = callback;
	}

	onUpdate(callback: Function): void
	{
		this.#onUpdate = callback;
	}

	onHide(callback: Function): void
	{
		this.#onHide = callback;
	}

	#createDialog(): EntitySelectorDialog
	{
		const handleItemChangeDebounced = Runtime.debounce(this.#handleItemChange, 10, this);

		return new EntitySelectorDialog({
			context: 'tasks-card',
			multiple: false,
			hideOnDeselect: true,
			enableSearch: true,
			entities: [
				{
					id: EntitySelectorEntity.Project,
				},
			],
			preselectedItems: this.#items,
			events: {
				'Item:onSelect': handleItemChangeDebounced,
				'Item:onDeselect': handleItemChangeDebounced,
				onHide: this.#handleHide,
				onDestroy: this.#clearOnUpdateOnce,
				onLoad: this.#insertSelectedGroup,
			},
		});
	}

	#handleHide = (): void => {
		this.#onHide?.();
		this.#clearOnUpdateOnce();
	};

	async #handleItemChange(): Promise<void>
	{
		const item = await this.#insertSelectedGroup();
		this.#updateGroup(item?.getId());
	}

	#insertSelectedGroup = async (): Promise<?Item> => {
		const item = this.#dialog.getSelectedItems()[0];
		if (!item)
		{
			return null;
		}

		await this.#insertGroup({
			id: item.getId(),
			name: item.getTitle(),
			image: item.getAvatar(),
			type: item.getEntityType(),
		});

		return item;
	};

	get #items(): ItemId[]
	{
		return [[EntitySelectorEntity.Project, this.#task.groupId]];
	}

	get #task(): TaskModel
	{
		return Core.getStore().getters[`${Model.Tasks}/getById`](this.#taskId);
	}

	#insertGroup(group: GroupModel): Promise<void>
	{
		return Core.getStore().dispatch(`${Model.Groups}/insert`, group);
	}

	#updateGroup(groupId: number): void
	{
		void taskService.update(
			this.#taskId,
			{
				groupId,
				stageId: 0,
			},
		);

		this.#onUpdate?.();
		this.#onUpdateOnce?.();
		this.#clearOnUpdateOnce();
	}

	#clearOnUpdateOnce = (): void => {
		this.#onUpdateOnce = null;
	};
}

export const groupDialog = new GroupDialog();
