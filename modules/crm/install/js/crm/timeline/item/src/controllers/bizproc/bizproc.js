import { ajax, Runtime, Uri, Text, Type, Loc } from 'main.core';
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

		const taskId = Text.toInteger(actionData?.taskId);
		if (action === 'Bizproc:Task:Open' && taskId > 0)
		{
			this.#openTask(taskId, actionData);

			return;
		}

		if (action === 'Bizproc:Task:Do' && taskId > 0)
		{
			const responsibleId = Text.toInteger(actionData?.responsibleId);
			if (responsibleId > 0 && Text.toInteger(item.getCurrentUser()?.userId) === responsibleId)
			{
				this.#doTask(taskId, actionData, item);

				return;
			}

			UI.Notification.Center.notify({
				content: Text.encode(Loc.getMessage('CRM_TIMELINE_ITEM_BIZPROC_TASK_DO_ACTION_ACCESS_DENIED')),
				autoHideDelay: 5000,
			});

			return;
		}

		const isTimelineOpen = action === 'Bizproc:Workflow:Timeline:Open';
		const isBizprocOpen = action === 'Bizproc:Workflow:Open';
		const isWorkflowTerminate = action === 'Bizproc:Workflow:Terminate';
		const workflowId = actionData?.workflowId;
		if (!workflowId)
		{
			return;
		}

		if (isTimelineOpen)
		{
			this.#openTimeline(workflowId);
		}

		if (isBizprocOpen)
		{
			this.#openSlider(`/company/personal/bizproc/${workflowId}/`);
		}

		if (isWorkflowTerminate)
		{
			this.#terminateWorkflow(workflowId);
		}
	}

	#openSlider(url)
	{
		Runtime
			.loadExtension('sidepanel')
			.then(() => {
				const options = {
					width: this.#detectSliderWidth(), // TODO extend UI with openSlider
					cacheable: false,
					loader: 'bizproc:workflow-info',
				};
				BX.SidePanel.Instance.open(
					Uri.addParam(url),
					options,
				);
			})
			.catch((response) => console.error(response.errors));
	}

	#openTimeline(workflowId): void
	{
		Runtime
			.loadExtension('bizproc.workflow.timeline')
			.then(() => {
				BX.Bizproc.Workflow.Timeline.open({ workflowId });
			})
			.catch((response) => console.error(response.errors));
	}

	#terminateWorkflow(workflowId): void
	{
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

	#detectSliderWidth(): number
	{
		if (window.innerWidth < 1500)
		{
			return null; // default slider width
		}

		return 1500 + Math.floor((window.innerWidth - 1500) / 3);
	}

	#doTask(taskId: number, actionData, item: ConfigurableItem)
	{
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

	#openTask(taskId: number, actionData)
	{
		let url = `/company/personal/bizproc/${taskId}/`;
		const userId = Text.toInteger(actionData?.userId);
		if (userId > 0)
		{
			url += `?USER_ID=${userId}`;
		}
		this.#openSlider(url);
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
