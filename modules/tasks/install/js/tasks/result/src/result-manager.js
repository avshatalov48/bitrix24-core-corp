import {EventEmitter} from 'main.core.events';
import {Result} from './result';

type initParams = {
	taskId: number,
	comments: [],
	isClosed: boolean,
	context: string
}

export class ResultManager
{
	static resultRegistry: Result[] = {};
	static instance = null;

	static getInstance()
	{
		if (!ResultManager.instance)
		{
			ResultManager.instance = new ResultManager();
		}
		return ResultManager.instance;
	}

	static showField()
	{
		const node = document.getElementById('IS_TASK_RESULT');
		if (
			!node
			|| !node.closest('label')
		)
		{
			return;
		}

		node.closest('label').classList.remove('--hidden');
	}

	static hideField()
	{
		const node = document.getElementById('IS_TASK_RESULT');
		if (
			!node
			|| !node.closest('label')
		)
		{
			return;
		}

		node.closest('label').classList.add('--hidden');
	}

	constructor()
	{
		this.init();
	}

	init()
	{
		const compatMode = {
			compatMode: true
		};

		EventEmitter.subscribe('OnUCFormBeforeShow',(event) => {
			this.onEditComment(event);
		}, compatMode);

		EventEmitter.subscribe('onPullEvent-tasks',(command, params) => {
			this.onPushResult(command, params);
		}, compatMode);

		EventEmitter.subscribe('onPull-tasks',(event) => {
			this.onPushResult(event.command, event.params);
		}, compatMode);
	}

	initResult(params: initParams)
	{
		const result = this.getResult(params.taskId);
		result.setComments(params.comments);

		if (params.context)
		{
			result.setContext(params.context);
		}
		if (params.isClosed)
		{
			result.setClosed(true);
		}

		return result;
	}

	getResult(taskId: number)
	{
		if (!ResultManager.resultRegistry[taskId])
		{
			ResultManager.resultRegistry[taskId] = new Result(taskId);
		}
		return ResultManager.resultRegistry[taskId];
	}

	onEditComment(event)
	{
		if (
			!event
			|| !event['id']
			|| event['id'][0].indexOf('TASK_') !== 0
		)
		{
			return;
		}

		const node = document.getElementById('IS_TASK_RESULT');
		if (!node)
		{
			return;
		}

		const taskId = +/\d+/.exec(event['id'][0]);
		const result = this.getResult(taskId);

		node.checked = result.isResult(event['id'][1]);
	}

	onPushResult(command, params)
	{
		if (command === 'task_update')
		{
			const taskId = parseInt(params.TASK_ID);
			const result = this.getResult(taskId);

			if (
				params.AFTER.STATUS
				&& (
					params.AFTER.STATUS == 4
					|| params.AFTER.STATUS == 5
				)
			)
			{
				result.setClosed(true);
			}
			else if (params.AFTER.STATUS)
			{
				result.setClosed(false);
			}

			return;
		}

		if (
			command !== 'task_result_create'
			&& command !== 'task_result_delete'
		)
		{
			return;
		}

		if (
			!params.result
			|| !params.result.taskId
			|| !params.result.commentId
		)
		{
			return;
		}

		const taskId = params.result.taskId;
		const result = this.getResult(taskId);

		if (command === 'task_result_create')
		{
			result.pushComment(params.result);
		}
		else if (command === 'task_result_delete')
		{
			result.deleteComment(params.result.commentId);
		}
	}
}