import { Extension, Runtime } from 'main.core';

import { EmptyStub, Footer } from 'tasks.flow.entity-selector';
import { Core } from 'tasks.v2.core';
import { EntitySelectorEntity, Model } from 'tasks.v2.const';
import { EntitySelectorDialog, type ItemId } from 'tasks.v2.lib.entity-selector-dialog';
import { groupService } from 'tasks.v2.provider.service.group-service';
import { taskService } from 'tasks.v2.provider.service.task-service';
import type { FlowModel } from 'tasks.v2.model.flows';
import type { GroupModel } from 'tasks.v2.model.groups';
import type { TaskModel } from 'tasks.v2.model.tasks';

class FlowDialog
{
	#taskId: number | string;
	#dialog: EntitySelectorDialog;
	#onUpdateOnce: Function | null = null;

	setTaskId(taskId: number | string): FlowDialog
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

		const dialog = new EntitySelectorDialog({
			context: 'tasks-card',
			width: 380,
			height: 370,
			multiple: false,
			hideOnDeselect: true,
			enableSearch: true,
			entities: [
				{
					id: EntitySelectorEntity.Flow,
					options: {
						onlyActive: true,
					},
				},
			],
			preselectedItems: this.#items,
			events: {
				'Item:onSelect': onItemChangeDebounced,
				'Item:onDeselect': onItemChangeDebounced,
				onHide: this.#clearOnUpdateOnce,
				onDestroy: this.#clearOnUpdateOnce,
			},
			recentTabOptions: {
				stub: EmptyStub,
				stubOptions: {
					showArrow: false,
				},
			},
		});

		if (Core.getParams().rights?.flow?.create)
		{
			const isFeatureTriable = Extension.getSettings('tasks.v2.component.fields.flow').get('isFeatureTriable');

			const footer = new Footer(dialog, {
				isFeatureTriable,
			});
			dialog.setFooter(footer.render());
		}

		return dialog;
	}

	async #onItemChange(): void
	{
		const item = this.#dialog.getSelectedItems()[0];
		const groupId = item?.getCustomData().get('groupId');
		if (item)
		{
			this.#insertFlow({
				id: item.getId(),
				name: item.getTitle(),
			});

			const group: GroupModel | undefined = Core.getStore().getters[`${Model.Groups}/getById`](groupId);
			if (!group)
			{
				await groupService.getGroup(groupId);
			}
		}

		this.#updateFlow(item?.getId() ?? 0, groupId ?? 0);
	}

	get #items(): ItemId[]
	{
		return [[EntitySelectorEntity.Flow, this.#task.flowId]];
	}

	get #task(): TaskModel
	{
		return Core.getStore().getters[`${Model.Tasks}/getById`](this.#taskId);
	}

	#insertFlow(flow: FlowModel): void
	{
		void Core.getStore().dispatch(`${Model.Flows}/insert`, flow);
	}

	#updateFlow(flowId: number, groupId: number): void
	{
		const creatorId = Core.getStore().getters[`${Model.Interface}/currentUserId`];
		void taskService.update(
			this.#taskId,
			{
				creatorId,
				flowId,
				groupId,
				stageId: 0,
			},
		);

		this.#onUpdateOnce?.();
		this.#clearOnUpdateOnce();
	}

	#clearOnUpdateOnce = (): void => {
		this.#onUpdateOnce = null;
	};
}

export const flowDialog = new FlowDialog();
