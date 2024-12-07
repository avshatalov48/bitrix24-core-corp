import { ajax } from 'main.core';
import { sendData } from 'ui.analytics';

export class ResultAction
{
	static instance = null;

	static getInstance()
	{
		if (!ResultAction.instance)
		{
			ResultAction.instance = new ResultAction();
		}

		return ResultAction.instance;
	}

	canCreateResult(taskId: number)
	{
		return true;
	}

	deleteFromComment(commentId: number)
	{
		ajax
			.runComponentAction('bitrix:tasks.widget.result', 'deleteFromComment', {
				mode: 'class',
				data: { commentId },
			})
			.catch(console.error);
	}

	createFromComment(commentId: number, shouldSendAnalytics = false)
	{
		const analyticsLabel = {
			tool: 'tasks',
			category: 'task_operations',
			event: 'status_summary_add',
			type: 'task',
			c_section: 'task',
			c_sub_section: 'task_card',
			c_element: 'comment_context_menu',
		};

		ajax
			.runComponentAction('bitrix:tasks.widget.result', 'createFromComment', {
				mode: 'class',
				data: { commentId },
			})
			.then(() => {
				if (shouldSendAnalytics)
				{
					sendData({
						...analyticsLabel,
						status: 'success',
					});
				}
			})
			.catch(() => {
				if (shouldSendAnalytics)
				{
					sendData({
						...analyticsLabel,
						status: 'error',
					});
				}
			});
	}
}
