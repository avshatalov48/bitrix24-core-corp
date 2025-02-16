import { ajax, Runtime, Text, Type, Loc } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { UI } from 'ui.notification';
import AvatarsStackSteps from '../../components/content-blocks/avatars-stack-steps';
import { ActionParams, Base } from '../base';
import ConfigurableItem from '../../configurable-item';
import { ButtonState } from '../../components/enums/button-state';
import { TaskUserStatus } from './enums/task-user-status';

export class Bizproc extends Base
{
	static isItemSupported(item: ConfigurableItem): boolean
	{
		const supportedItemTypes = [
			'BizprocWorkflowStarted',
			'BizprocWorkflowCompleted',
			'BizprocWorkflowTerminated',
			'BizprocTaskCreation',
			'BizprocTaskCompleted',
			'BizprocCommentAdded',
			'BizprocCommentRead',
			'BizprocTaskDelegated',
			'Activity:BizprocWorkflowCompleted',
			'Activity:BizprocCommentAdded',
			'Activity:BizprocTask',
		];

		return supportedItemTypes.includes(item.getType());
	}

	getContentBlockComponents(item: ConfigurableItem): Object
	{
		return {
			AvatarsStackSteps,
		};
	}

	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const { action, actionType, actionData } = actionParams;

		if (actionType !== 'jsEvent')
		{
			return;
		}

		const actionHandlers = {
			'Bizproc:Task:Open': () => this.#openWorkflowTaskSlider(actionData),
			'Bizproc:Task:Do': () => this.#handleTaskAction(actionData, item),
			'Bizproc:Workflow:Timeline:Open': () => this.#openTimeline(actionData),
			'Bizproc:Workflow:Open': () => this.#openWorkflowSlider(actionData),
			'Bizproc:Workflow:Terminate': () => this.#terminateWorkflow(actionData),
			'Bizproc:Workflow:Log': () => this.#openWorkflowLogSlider(actionData),
		};

		const handler = actionHandlers[action];

		if (handler)
		{
			handler();
		}
	}

	#handleTaskAction(actionData: Object, item: ConfigurableItem): void
	{
		const responsibleId = Text.toInteger(actionData?.responsibleId);
		if (responsibleId > 0 && Text.toInteger(item.getCurrentUser()?.userId) === responsibleId)
		{
			this.#doTask(actionData, item);
		}

		UI.Notification.Center.notify({
			content: Text.encode(Loc.getMessage('CRM_TIMELINE_ITEM_BIZPROC_TASK_DO_ACTION_ACCESS_DENIED')),
			autoHideDelay: 5000,
		});
	}

	#openWorkflowLogSlider(actionData)
	{
		this.#openSlider(actionData, (Router, { workflowId }) => {
			if (Router && workflowId)
			{
				Router.openWorkflowLog(workflowId);
			}
		});
	}

	#openWorkflowSlider(actionData)
	{
		this.#openSlider(actionData, (Router, { workflowId }) => {
			if (Router && workflowId)
			{
				Router.openWorkflow(workflowId);
			}
		});
	}

	#openWorkflowTaskSlider(actionData) {
		this.#openSlider(actionData, (Router, { taskId, userId }) => {
			if (Router && taskId)
			{
				Router.openWorkflowTask(Text.toInteger(taskId), Text.toInteger(userId));
			}
		});
	}

	async #openSlider(actionData, callback)
	{
		if (!actionData)
		{
			return;
		}

		try
		{
			const { Router } = await Runtime.loadExtension('bizproc.router');
			callback(Router, actionData);
		}
		catch (e)
		{
			console.error(e);
		}
	}

	#openTimeline(actionData: Object): void
	{
		const workflowId = actionData?.workflowId;
		if (!workflowId)
		{
			return;
		}

		Runtime
			.loadExtension('bizproc.workflow.timeline')
			.then(() => {
				BX.Bizproc.Workflow.Timeline.open({ workflowId });
			})
			.catch((response) => console.error(response.errors));
	}

	#terminateWorkflow(actionData: Object): void
	{
		const workflowId = actionData?.workflowId;
		if (!workflowId)
		{
			return;
		}

		ajax.runAction('bizproc.workflow.terminate', { data: { workflowId } })
			.catch((response) => {
				response.errors.forEach((error) => {
					UI.Notification.Center.notify({
						content: error.message,
						autoHideDelay: 5000,
					});
				});
			});
	}

	#doTask(actionData: Object, item: ConfigurableItem)
	{
		const taskId = actionData?.taskId;
		if (!taskId)
		{
			return;
		}

		const value = actionData?.value;
		const name = actionData?.name;

		if (Type.isStringFilled(name) && Type.isStringFilled(value))
		{
			const buttons = (
				Object.values(TaskUserStatus)
					.map((status) => {
						return item.getLayoutFooterButtonById(`status_${status}`);
					})
					.filter((button) => button)
			);

			buttons.forEach((button) => {
				button.setButtonState(ButtonState.DISABLED);
			});

			const data = { taskId, taskRequest: { [name]: value } };
			ajax.runAction('bizproc.task.do', { data })
				.then(() => {}) // waiting push
				.catch((response) => {
					response.errors.forEach((error) => {
						UI.Notification.Center.notify({
							content: Text.encode(error.message),
							autoHideDelay: 5000,
						});
					});

					buttons.forEach((button) => {
						button.setButtonState(ButtonState.DEFAULT);
					});
				})
			;
		}
	}

	onAfterItemLayout(item: ConfigurableItem, options): void
	{
		EventEmitter.emit(
			'BX.Crm.Timeline.Items.Bizproc:onAfterItemLayout',
			{
				target: item.getWrapper(),
				id: item.getId(),
				type: item.getType(),
				options,
			},
		);
	}
}
