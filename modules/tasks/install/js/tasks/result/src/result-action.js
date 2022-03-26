import {ajax} from 'main.core';

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

	constructor()
	{

	}

	canCreateResult(taskId: number)
	{
		return true;
	}

	deleteFromComment(commentId: number)
	{
		ajax.runComponentAction('bitrix:tasks.widget.result', 'deleteFromComment', {
			mode: 'class',
			data: {
				commentId: commentId
			}
		}).then(
			function(response)
			{
				if (!response.data)
				{
					return;
				}

			}.bind(this)
		);
	}

	createFromComment(commentId: number)
	{
		ajax.runComponentAction('bitrix:tasks.widget.result', 'createFromComment', {
			mode: 'class',
			data: {
				commentId: commentId
			}
		}).then(
			function(response)
			{
				if (!response.data)
				{
					return;
				}

			}.bind(this)
		);
	}
}