import { EntitySelectorDialog } from './dialog/entity-selector-dialog';
import { Tag, Text, Loc, Dom, Event, Type, ajax } from 'main.core';
import { EmptyStub } from './dialog/element/empty-stub';

import type { TaskEditToggleFlowParams } from './dialog/toggle-flow/type/task-edit-toggle-flow';
import type { TaskViewToggleFlowParams } from './dialog/toggle-flow/type/task-view-toggle-flow';

import './css/style.css';

type FlowData = {
	id: number,
	name: ?string,
	efficiency: ?number,
}

type FlowParams = {
	id: number,
	name: ?string,
	efficiency: ?number,

	limitCode: string,
	isFeatureEnabled: boolean,
	isFeatureTrialable: boolean,
}

type Params = {
	taskId: Number,
	canEditTask: boolean,
	isExtranet: boolean,

	toggleFlowParams: TaskViewToggleFlowParams | TaskEditToggleFlowParams,
	flowParams: FlowParams,
};

export {
	EmptyStub,
};

export class EntitySelector
{
	#flowSelectorContainer: ?HTMLElement;

	#taskId: Number;
	#isExtranet: boolean;
	#canEditTask: boolean;

	#toggleFlowParams: TaskViewToggleFlowParams | TaskEditToggleFlowParams;
	#flowParams: FlowParams;
	#dialog: ?EntitySelectorDialog;

	constructor(params: Params)
	{
		this.#taskId = params.taskId;
		this.#isExtranet = params.isExtranet;
		this.#canEditTask = params.canEditTask;

		this.#toggleFlowParams = params.toggleFlowParams;
		this.#flowParams = params.flowParams;

		this.#flowSelectorContainer = document.getElementById('tasks-flow-selector-container');

		this.#subscribeEvents();
	}

	#subscribeEvents(): void
	{
		BX.PULL.subscribe({
			type: BX.PullClient.SubscriptionType.Server,
			moduleId: 'tasks',
			command: 'task_update',
			callback: this.#onTaskUpdated.bind(this),
		});
	}

	async #onTaskUpdated(params: Object, extra: Object, command: string): void
	{
		const isEventByCurrentTask = parseInt(params?.TASK_ID, 10) === this.#taskId;
		const isEventContainsFlow = !Type.isUndefined(params.AFTER.FLOW_ID);

		if (!isEventByCurrentTask || !isEventContainsFlow)
		{
			return;
		}

		const flowId = Number(params.AFTER.FLOW_ID ?? 0);
		const isFlowChange = this.#flowParams.id !== flowId;

		if (!isFlowChange)
		{
			return;
		}

		let flowData: ?FlowData = { id: 0, name: '', efficiency: 0 };
		if (flowId !== 0)
		{
			flowData = await this.#loadFlowData(flowId);
		}

		this.#updateFlow(flowData);
	}

	async #loadFlowData(flowId: number): ?FlowData
	{
		const flowResponse = await ajax.runAction('tasks.flow.Flow.get', {
			data: {
				flowId,
			},
		});

		return flowResponse.data;
	}

	#updateFlow(flowData: FlowData): void
	{
		this.#flowParams.id = flowData.id;
		this.#flowParams.name = flowData.name;
		this.#flowParams.efficiency = flowData.efficiency;

		this.show(this.#flowSelectorContainer);
	}

	show(target: HTMLElement): void
	{
		if (!Type.isDomNode(target))
		{
			throw new TypeError('HTMLElement for render flow entity selector not found');
		}

		Dom.clean(target);
		Dom.append(this.#render(), target);
	}

	#render(): HTMLElement
	{
		const flowFeatureEnabledClass = this.#flowParams.isFeatureEnabled ? '' : '--tariff-lock';
		const flowCanChangeClass = this.#canEditTask ? 'ui-btn-dropdown' : '--disable';
		const flowBtnClasses = 'ui-btn ui-btn-round ui-btn-xs ui-btn-no-caps';

		const container = Tag.render`
			<button 
				class="tasks-flow__selector ${flowBtnClasses} ${flowFeatureEnabledClass} ${flowCanChangeClass}" 
				id="tasks-flow-selector"
			>		
			</button>
		`;

		if (this.#canEditTask)
		{
			Event.bind(container, 'click', () => {
				this.#dialog = this.#dialog ?? this.#createDialog();
				this.#dialog.show(this.#flowSelectorContainer);
			});
		}

		if (this.#flowParams.id)
		{
			Dom.addClass(container, 'ui-btn-secondary-light');
			container.append(this.#renderFlowName(this.#flowParams.name));
			container.append(this.#renderEfficiency(this.#flowParams.efficiency));
		}
		else
		{
			Dom.addClass(container, 'ui-btn-base-light');
			container.append(
				this.#renderFlowName(Loc.getMessage('TASKS_FLOW_ENTITY_SELECTOR_FLOW_EMPTY')),
			);

			if (!this.#canEditTask)
			{
				Dom.addClass(container, '--hide');
			}
		}

		return container;
	}

	#createDialog(): Dialog
	{
		this.#dialog = new EntitySelectorDialog({
			isExtranet: this.#isExtranet,
			toggleFlowParams: this.#toggleFlowParams,

			flowId: this.#flowParams.id,
			flowLimitCode: this.#flowParams.limitCode,
			isFeatureEnabled: this.#flowParams.isFeatureEnabled,
			isFeatureTrialable: this.#flowParams.isFeatureTrialable,
		});

		return this.#dialog;
	}

	#renderFlowName(name: string): HTMLElement
	{
		return Tag.render`
			<span class="tasks-flow__selector-text">
				${Text.encode(name)}
			</span>
		`;
	}

	#renderEfficiency(efficiency: number): HTMLElement
	{
		return Tag.render`
			<span class="tasks-flow__selector-efficiency">
				${this.#prepareEfficiency(efficiency)}
			</span>
		`;
	}

	#prepareEfficiency(efficiency: number): string
	{
		return `${efficiency}%`;
	}
}
