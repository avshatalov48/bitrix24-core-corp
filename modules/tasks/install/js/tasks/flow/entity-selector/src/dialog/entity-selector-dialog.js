import { Dialog } from 'ui.entity-selector';
import { InfoHelper } from 'ui.info-helper';
import { Footer } from './element/footer';
import { Runtime } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { ToggleFlowFactory } from './toggle-flow/toggle-flow-factory';

import type { TaskViewToggleFlowParams } from './toggle-flow/type/task-view-toggle-flow';
import type { TaskEditToggleFlowParams } from './toggle-flow/type/task-edit-toggle-flow';
import type { AbstractToggleFlow } from './toggle-flow/abstract-toggle-flow';

type FlowSelectorDialogParams = {
	isExtranet: boolean,
	toggleFlowParams: TaskEditToggleFlowParams | TaskViewToggleFlowParams,

	flowId: number,
	flowLimitCode: string,
	isFeatureEnabled: boolean,
	isFeatureTrialable: boolean,
}

export class EntitySelectorDialog
{
	#params: FlowSelectorDialogParams;
	#toggleFlow: AbstractToggleFlow;

	#dialog: Dialog;
	#selectedItemBeforeUpdate: Item;

	constructor(params: FlowSelectorDialogParams)
	{
		this.#params = params;

		this.#toggleFlow = ToggleFlowFactory.get(this.#params.toggleFlowParams);
		this.#selectedItemBeforeUpdate = null;
	}

	show(target: HTMLElement): void
	{
		if (!this.#params.isFeatureEnabled)
		{
			InfoHelper.show(this.#params.flowLimitCode);

			return;
		}

		if (!this.#dialog)
		{
			this.#createDialog(target);
		}

		this.#dialog.show();
	}

	#createDialog(target: HTMLElement): Dialog
	{
		this.#dialog = new Dialog({
			targetNode: target,
			width: 350,
			height: 400,
			multiple: false,
			dropdownMode: true,
			enableSearch: true,
			cacheable: true,
			preselectedItems: [['flow', this.#params.flowId]],
			entities: [
				{
					id: 'flow',
					options: {
						onlyActive: true,
					},
					dynamicLoad: true,
					dynamicSearch: true,
				},
			],
			events: {
				'Item:onBeforeSelect': (event: BaseEvent) => {
					const dialog = event.getTarget();
					this.#selectedItemBeforeUpdate = dialog.getSelectedItems()[0];
				},
				'Item:onBeforeDeselect': (event: BaseEvent) => {
					const dialog = event.getTarget();
					this.#selectedItemBeforeUpdate = dialog.getSelectedItems()[0];

					dialog.hide();
				},
				'Item:onSelect': (event: BaseEvent) => {
					this.#toggleFlow.onSelectFlow(event, this.#selectedItemBeforeUpdate);
				},
				'Item:onDeselect': (event: BaseEvent) => {
					setTimeout(() => {
						const dialog = event.getTarget();
						this.#toggleFlow.onDeselectFlow(event, dialog.getSelectedItems()[0] ?? null);
					}, 100);
				},
				'Search:onItemCreateAsync': (event) => {
					return new Promise((resolve) => {
						/** @type  {BX.UI.EntitySelector.Item} */
						const { searchQuery } = event.getData();
						/** @type  {BX.UI.EntitySelector.Dialog} */
						const dialog = event.getTarget();

						this.#createFlow(searchQuery.getQuery())
							.then((createdFlowData) => {
								if (createdFlowData)
								{
									const item = dialog.addItem({
										tabs: 'recents',
										id: createdFlowData.id,
										entityId: 'flow',
										title: createdFlowData.name,
										customData: {
											groupId: createdFlowData.groupId,
											templateId: createdFlowData.templateId,
										},
									});
									item.select();

									resolve();
								}
								else
								{
									resolve();
								}
							})
						;
					});
				},
			},
			searchOptions: {
				allowCreateItem: !this.#params.isExtranet,
				footerOptions: {
					label: BX.Loc.getMessage('TASKS_FLOW_ENTITY_SELECTOR_CREATE_BUTTON'),
				},
			},
			recentTabOptions: {
				stub: 'BX.Tasks.Flow.EmptyStub',
				stubOptions: {
					showArrow: !this.#params.isExtranet,
				},
			},
		});

		if (!this.#params.isExtranet)
		{
			this.#dialog = this.#addFooter(this.#dialog);
		}

		return this.#dialog;
	}

	#createFlow(flowName): Promise
	{
		return Runtime.loadExtension('tasks.flow.edit-form')
			.then((exports) => exports.EditForm.createInstance({ flowName }))
			.then((editForm) => {
				return new Promise((resolve) => {
					editForm.subscribe('afterSave', (baseEvent) => {
						resolve(baseEvent.getData());
					});
					editForm.subscribe('afterClose', () => {
						resolve();
					});
				});
			});
	}

	#addFooter(dialog: Dialog): Dialog
	{
		const footer = new Footer(this.#dialog);
		dialog.setFooter(footer.render());

		return dialog;
	}
}
