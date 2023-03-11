export class Result
{
	taskId: null;
	isClosed: false;
	comments: [];
	context: null;

	constructor(taskId: number)
	{
		this.taskId = taskId;
	}

	setClosed(value: boolean)
	{
		this.isClosed = value;
	}

	setComments(comments: [])
	{
		this.comments = comments;
	}

	setContext(context: string)
	{
		this.context = context;
	}

	isResult(commentId: number)
	{
		if (
			this.comments
			&& this.comments[commentId]
		)
		{
			return true;
		}

		return false;
	}

	pushComment(result)
	{
		this.comments[result.commentId] = result;
	}

	deleteComment(commentId)
	{
		this.comments[commentId] && delete this.comments[commentId];
	}

	canSetAsResult(commentId)
	{
		if (this.comments[commentId])
		{
			return false;
		}

		return true;
	}

	canUnsetAsResult(commentId)
	{
		if (!this.comments[commentId])
		{
			return false;
		}

		return true;
	}
}