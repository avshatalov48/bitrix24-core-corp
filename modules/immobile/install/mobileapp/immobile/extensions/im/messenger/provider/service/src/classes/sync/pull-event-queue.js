/**
 * @module im/messenger/provider/service/classes/sync/pull-event-queue
 */
jn.define('im/messenger/provider/service/classes/sync/pull-event-queue', (require, exports, module) => {

	/**
	 * @class PullEventQueue
	 */
	class PullEventQueue
	{
		constructor()
		{
			this.queue = [];
		}

		isEmpty()
		{
			return this.queue.length === 0;
		}

		enqueue(event)
		{
			this.queue.push(event);
		}

		dequeue()
		{
			return this.queue.shift();
		}
	}

	module.exports = {
		PullEventQueue,
	};
});
