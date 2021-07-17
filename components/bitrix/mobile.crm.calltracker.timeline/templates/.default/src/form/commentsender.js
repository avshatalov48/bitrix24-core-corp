import Queue from './queue';

export default class CommentSender extends Queue
{
	static instance = null;

	static getInstance()
	{
		if (CommentSender.instance === null)
		{
			CommentSender.instance = new CommentSender();
		}
		return CommentSender.instance;
	}
}