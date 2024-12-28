/**
 * @module crm/timeline/controllers/bizproc
 */
jn.define('crm/timeline/controllers/bizproc', (require, exports, module) => {
	const { TimelineBaseController } = require('crm/controllers/base');
	const { inAppUrl } = require('in-app-url');
	const { Type } = require('type');
	const { NotifyManager } = require('notify-manager');
	const { Loc } = require('loc');

	const SUPPORTED_ACTIONS = Object.freeze({
		TASK_OPEN: 'Bizproc:Task:Open',
		TASK_DO: 'Bizproc:Task:Do',
		TIMELINE_OPEN: 'Bizproc:Workflow:Timeline:Open',
		WORKFLOW_OPEN: 'Bizproc:Workflow:Open',
		WORKFLOW_TERMINATE: 'Bizproc:Workflow:Terminate',
	});

	class TimelineBizprocController extends TimelineBaseController
	{
		static getSupportedActions()
		{
			return Object.values(SUPPORTED_ACTIONS);
		}

		onItemAction({ action, actionParams = {} })
		{
			switch (action)
			{
				case SUPPORTED_ACTIONS.TASK_OPEN:
					this.#openTask(actionParams);
					break;
				case SUPPORTED_ACTIONS.TASK_DO:
					this.#doTask(actionParams);
					break;
				case SUPPORTED_ACTIONS.TIMELINE_OPEN:
					this.#openTimeline(actionParams);
					break;
				case SUPPORTED_ACTIONS.WORKFLOW_OPEN:
					this.#openWorkflow(actionParams);
					break;
				case SUPPORTED_ACTIONS.WORKFLOW_TERMINATE:
					this.#terminateWorkflow(actionParams);
					break;
				default:
					break;
			}
		}

		#openTask(data)
		{
			const taskId = this.#getTaskId(data);
			if (taskId <= 0)
			{
				return;
			}

			let url = `/company/personal/bizproc/${taskId}/`;

			const userId = BX.prop.getInteger(data, 'userId', 0);
			if (userId > 0)
			{
				url += `?USER_ID=${userId}`;
			}

			inAppUrl.open(url);
		}

		#doTask(data)
		{
			const taskId = this.#getTaskId(data);
			if (taskId <= 0)
			{
				return;
			}

			const responsibleId = BX.prop.getInteger(data, 'responsibleId', 0);
			if (responsibleId <= 0 || responsibleId !== Number(env.userId))
			{
				NotifyManager.showError(
					Loc.getMessage('M_CRM_TIMELINE_ITEM_BIZPROC_TASK_DO_ACTION_ACCESS_DENIED'),
				);

				return;
			}

			const name = BX.prop.getString(data, 'name', '');
			const value = BX.prop.getString(data, 'value', '');
			if (!Type.isStringFilled(name) || !Type.isStringFilled(value))
			{
				return;
			}

			this.item.showLoader();

			BX.ajax.runAction('bizproc.task.do', { data: { taskId, taskRequest: { [name]: value } } })
				.then(() => {}) // waiting push
				.catch((response) => {
					NotifyManager.showErrors(response.errors);
					this.item.hideLoader();
				})
			;
		}

		#openTimeline(data)
		{
			const workflowId = this.#getWorkflowId(data);
			if (!Type.isStringFilled(workflowId))
			{
				return;
			}

			void requireLazy('bizproc:workflow/timeline')
				.then(({ WorkflowTimeline }) => {
					if (WorkflowTimeline)
					{
						void WorkflowTimeline.open(PageManager, { workflowId });
					}
				})
			;
		}

		#openWorkflow(data)
		{
			const workflowId = this.#getWorkflowId(data);
			if (!Type.isStringFilled(workflowId))
			{
				return;
			}

			void requireLazy('bizproc:workflow/details')
				.then(({ WorkflowDetails }) => {
					if (WorkflowDetails)
					{
						WorkflowDetails.open({ workflowId });
					}
				})
			;
		}

		#terminateWorkflow(data)
		{
			const workflowId = this.#getWorkflowId(data);
			if (!Type.isStringFilled(workflowId))
			{
				return;
			}

			BX.ajax.runAction('bizproc.workflow.terminate', { data: { workflowId } })
				.catch((response) => {
					NotifyManager.showErrors(response.errors);
				});
		}

		#getTaskId(data = {})
		{
			return BX.prop.getInteger(data, 'taskId', 0);
		}

		#getWorkflowId(data = {})
		{
			return BX.prop.getString(data, 'workflowId', '');
		}
	}

	module.exports = { TimelineBizprocController };
});
