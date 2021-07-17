import Queue from './queue';

export default class FileSender extends Queue
{
	check()
	{
		if (this.erroredQueue.length <= 0 && this.queue.length > 0)
		{
			return this.execute(this.queue[0]);
		}

		return this.finish();
	}
}
