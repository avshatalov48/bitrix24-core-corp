import { Extension } from 'main.core';
import type { Store } from 'ui.vue3.vuex';
import type { Item } from 'ui.entity-selector';

import { Core } from 'tasks.v2.core';
import { EntitySelectorEntity, Model } from 'tasks.v2.const';
import { CrmMappers } from 'tasks.v2.provider.service.crm-service';
import { taskService } from 'tasks.v2.provider.service.task-service';
import { EntitySelectorDialog, type ItemId } from 'tasks.v2.lib.entity-selector-dialog';
import type { TaskModel } from 'tasks.v2.model.tasks';
import type { CrmItemModel } from 'tasks.v2.model.crm-items';

const crmIntegration = Extension.getSettings('tasks.v2.component.fields.crm').crmIntegration;
const dynamicTypeIds = Object.entries(crmIntegration)
	.filter(([entityId, enabled]) => enabled === 'Y' && entityId.startsWith('DYNAMIC_'))
	.map(([entityId]) => Number(entityId.slice(8)))
;

class CrmDialog
{
	#taskId: number | string;
	#dialog: EntitySelectorDialog;
	#onUpdateOnce: Function | null = null;
	#onCloseOnce: Function | null = null;

	setTaskId(taskId: number | string): CrmDialog
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

	onCloseOnce(callback: Function): CrmDialog
	{
		this.#onCloseOnce = callback;

		return this;
	}

	#createDialog(): EntitySelectorDialog
	{
		let changed = false;
		const handleChanged = (): void => {
			changed = true;
		};

		const handleClose = async (): Promise<void> => {
			if (changed)
			{
				void this.#handleItemChange();

				changed = false;
			}

			this.#onCloseOnce?.();
			this.#onCloseOnce = null;
		};

		return new EntitySelectorDialog({
			context: 'tasks-card',
			multiple: true,
			enableSearch: true,
			entities: [
				EntitySelectorEntity.Deal,
				EntitySelectorEntity.Contact,
				EntitySelectorEntity.Company,
				EntitySelectorEntity.Lead,
				EntitySelectorEntity.SmartInvoice,
				EntitySelectorEntity.DynamicMultiple,
			].map((entityId: string) => ({
				id: entityId,
				dynamicLoad: true,
				dynamicSearch: true,
				options: {
					dynamicTypeIds,
					showTab: true,
				},
			})),
			preselectedItems: this.#items,
			events: {
				'Item:onSelect': handleChanged,
				'Item:onDeselect': handleChanged,
				onHide: this.#clearOnUpdateOnce,
				onDestroy: this.#clearOnUpdateOnce,
				onLoad: this.#insertSelectedCrmItems,
			},
			popupOptions: {
				events: {
					onClose: handleClose,
				},
			},
		});
	}

	async #handleItemChange(): Promise<void>
	{
		const crmItems = await this.#insertSelectedCrmItems();

		this.#updateTask(crmItems.map(({ id }) => id));
	}

	#insertSelectedCrmItems = async (): Promise<CrmItemModel[]> => {
		const crmItems = this.#dialog.getSelectedItems().map((item: Item) => this.#mapItemToModel(item));

		await this.#insertCrmItems(crmItems);

		return crmItems;
	};

	#updateTask(crmItemIds: string[]): void
	{
		void taskService.update(
			this.#taskId,
			{ crmItemIds },
		);

		this.#onUpdateOnce?.();
		this.#clearOnUpdateOnce();
	}

	#mapItemToModel(item: Item): CrmItemModel
	{
		const entityInfo = item.getCustomData().get('entityInfo');

		return {
			id: CrmMappers.mapId(entityInfo.type, item.getId()),
			entityId: item.getId(),
			type: item.getEntityId(),
			typeName: entityInfo.typeNameTitle,
			title: item.getTitle(),
			link: entityInfo.url,
		};
	}

	#clearOnUpdateOnce = (): void => {
		this.#onUpdateOnce = null;
	};

	get #items(): ItemId[]
	{
		return this.#task.crmItemIds?.map((id) => CrmMappers.splitId(id)) ?? [];
	}

	get #task(): TaskModel
	{
		return this.$store.getters[`${Model.Tasks}/getById`](this.#taskId);
	}

	async #insertCrmItems(crmItems: CrmItemModel[]): Promise<void>
	{
		await this.$store.dispatch(`${Model.CrmItems}/upsertMany`, crmItems);
	}

	get $store(): Store
	{
		return Core.getStore();
	}
}

export const crmDialog = new CrmDialog();
