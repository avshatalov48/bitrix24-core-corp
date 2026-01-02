import { EntitySelectorDialog } from './dialog/entity-selector-dialog';
import { Loc, Event, Type, ajax } from 'main.core';
import { EmptyStub } from './dialog/element/empty-stub';
import { Footer } from './dialog/element/footer';
import {
	Button,
	ButtonIcon,
	ButtonState,
	ButtonColor,
	ButtonManager,
	AirButtonStyle,
	ButtonCounterColor,
	ButtonCounterStyle,
} from 'ui.buttons';

import type { TaskEditToggleFlowParams } from './dialog/toggle-flow/type/task-edit-toggle-flow';
import type { TaskViewToggleFlowParams } from './dialog/toggle-flow/type/task-view-toggle-flow';

import './css/style.css';

type FlowData = {
	id: number,
	name: ?string,
	efficiency: ?number,
}

export type FlowParams = {
	id: number,
	name: ?string,
	efficiency: ?number,

	limitCode: string,
	isFeatureEnabled: boolean,
	isFeatureTrialable: boolean,

	context?: string,
}

type Params = {
	taskId: Number,
	canEditTask: boolean,

	toggleFlowParams: TaskViewToggleFlowParams | TaskEditToggleFlowParams,
	flowParams: FlowParams,
	canCreateFlow: boolean,
};

export {
	EmptyStub,
	Footer,
};

export class EntitySelector
{
	#flowSelectorBtn: ?Button;

	#taskId: Number;
	#canEditTask: boolean;
	#canCreateFlow: boolean;

	#toggleFlowParams: TaskViewToggleFlowParams | TaskEditToggleFlowParams;
	#flowParams: FlowParams;
	#dialog: ?EntitySelectorDialog;

	constructor(params: Params)
	{
		this.#taskId = params.taskId;
		this.#canEditTask = params.canEditTask;

		this.#canCreateFlow = params.canCreateFlow;

		this.#toggleFlowParams = params.toggleFlowParams;
		this.#flowParams = params.flowParams;

		this.#flowSelectorBtn = ButtonManager.createFromNode(document.getElementById('tasks-flow-selector-container'));

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

		this.show(this.#flowSelectorBtn.getContainer());
	}

	show(target: HTMLElement): void
	{
		if (!Type.isDomNode(target))
		{
			throw new TypeError('HTMLElement for render flow entity selector not found');
		}

		this.#updateFlowBtn();
	}

	#updateFlowBtn(): void
	{
		this.#flowSelectorBtn.addClass('tasks-flow__selector');
		this.#flowSelectorBtn.setDropdown(true);
		this.#flowSelectorBtn.setState(this.#canEditTask ? '' : ButtonState.DISABLED);
		this.#flowSelectorBtn.setIcon(this.#flowParams.isFeatureEnabled ? '' : ButtonIcon.LOCK, 'right');

		if (this.#flowParams.id)
		{
			this.#flowSelectorBtn.setText(this.#flowParams.name);

			if (this.#flowSelectorBtn.hasAirDesign())
			{
				this.#flowSelectorBtn.setStyle(AirButtonStyle.SELECTION);
				this.#flowSelectorBtn.setRightCounter(null);
				this.#flowSelectorBtn.setRightCounter({
					color: ButtonCounterColor.PRIMARY,
					style: ButtonCounterStyle.FILLED_INVERTED,
					value: this.#flowParams.efficiency,
					useSymbolPercent: true,
					maxValue: 100,
				});
			}
			else
			{
				this.#flowSelectorBtn.setColor(ButtonColor.SECONDARY_LIGHT);
				this.#flowSelectorBtn.setCounter(this.#prepareEfficiency(this.#flowParams.efficiency));
			}
			this.#flowSelectorBtn.setMaxWidth(350);
		}
		else
		{
			this.#flowSelectorBtn.setText(Loc.getMessage('TASKS_FLOW_ENTITY_SELECTOR_FLOW_EMPTY'));

			if (this.#flowSelectorBtn.hasAirDesign())
			{
				this.#flowSelectorBtn.setStyle(AirButtonStyle.OUTLINE);
				this.#flowSelectorBtn.setRightCounter(null);
			}
			else
			{
				this.#flowSelectorBtn.setColor(ButtonColor.BASE_LIGHT);
				this.#flowSelectorBtn.setCounter(null);
			}
			this.#flowSelectorBtn.setMaxWidth(null);

			if (this.#canEditTask === false)
			{
				this.#flowSelectorBtn.addClass('--hide');
			}
			else
			{
				this.#flowSelectorBtn.removeClass('--hide');
			}
		}

		if (this.#canEditTask)
		{
			Event.bind(this.#flowSelectorBtn.getContainer(), 'click', () => {
				this.#dialog = this.#dialog ?? this.#createDialog();
				this.#dialog.show(this.#flowSelectorBtn.getContainer());
			});
		}
	}

	#createDialog(): Dialog
	{
		this.#dialog = new EntitySelectorDialog({
			toggleFlowParams: this.#toggleFlowParams,
			flowParams: this.#flowParams,
			canCreateFlow: this.#canCreateFlow,
		});

		return this.#dialog;
	}

	#prepareEfficiency(efficiency: number): string
	{
		return `${efficiency}%`;
	}
}
