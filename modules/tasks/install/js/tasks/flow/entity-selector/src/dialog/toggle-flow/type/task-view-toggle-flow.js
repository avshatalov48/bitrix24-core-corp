import { AbstractToggleFlow } from '../abstract-toggle-flow';
import { ajax } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { Scope } from '../dictionary/scope';

import type { Item } from 'ui.entity-selector';

export type TaskViewToggleFlowParams = {
	scope: Scope,

	taskId: number,
	flowId: number,
}

export class TaskViewToggleFlow extends AbstractToggleFlow
{
	#taskId: number;
	#currentFlowId: number;

	constructor(params: TaskViewToggleFlowParams)
	{
		super();

		this.#taskId = params.taskId;
		this.#currentFlowId = params.flowId;
	}

	onSelectFlow(event: BaseEvent, itemBeforeUpdate: Item): void
	{
		const selectedItem = event.getData().item;
		const flowId = parseInt(selectedItem.id, 10);

		this.#updateFlow(flowId);
	}

	onDeselectFlow(event: BaseEvent, selectedItem: ?Item): void
	{
		const unSelectedItem = event.getData().item;
		const unSelectedFlowId = parseInt(unSelectedItem.id, 10);

		if (unSelectedFlowId === this.#currentFlowId)
		{
			this.#unBindFlow();
		}
	}

	#unBindFlow(): void
	{
		this.#updateFlow(0);
	}

	async #updateFlow(flowId: number): void
	{
		const flowIdBeforeUpdate = this.#currentFlowId;
		this.#currentFlowId = flowId;

		try
		{
			await ajax.runAction('tasks.task.update', {
				data: {
					taskId: this.#taskId,
					fields: {
						FLOW_ID: flowId,
					},
				},
			});
		}
		catch
		{
			this.#currentFlowId = flowIdBeforeUpdate;
		}
	}
}
