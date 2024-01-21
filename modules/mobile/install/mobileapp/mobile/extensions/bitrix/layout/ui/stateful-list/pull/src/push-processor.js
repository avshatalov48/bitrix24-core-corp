/**
 * @module layout/ui/stateful-list/pull/src/push-processor
 */
jn.define('layout/ui/stateful-list/pull/src/push-processor', (require, exports, module) => {
	const { debounce } = require('utils/function');
	const { command } = require('layout/ui/stateful-list/pull/src/command');

	const queueItemsStatus = {
		WAITING: 'WAITING',
		EXECUTED: 'EXECUTED',
	};

	class PushProcessor
	{
		constructor(data)
		{
			this.eventCallbacks = data.eventCallbacks;
			this.queue = [];
			this.debounceExecuteNextInQueue = debounce(this.executeNextInQueue, 200, this);
		}

		addToQueue(eventName, items)
		{
			if (this.eventCallbacks[eventName])
			{
				this.queue.push({
					eventName,
					items,
					status: queueItemsStatus.WAITING,
				});
				this.debounceExecuteNextInQueue();
			}
		}

		removeFirstAndExecNext()
		{
			this.removeFirstCallbackFromQueue();
			this.executeNextInQueue();
		}

		removeFirstCallbackFromQueue()
		{
			if (this.queue.length > 0)
			{
				this.queue.shift();
			}
		}

		executeNextInQueue()
		{
			if (this.queue.length > 0 && this.queue[0].status === queueItemsStatus.WAITING)
			{
				this.optimizeQueue();
				const firstInQueue = this.queue[0];
				firstInQueue.status = queueItemsStatus.EXECUTED;
				const result = this.eventCallbacks[firstInQueue.eventName](firstInQueue.items);
				if (result instanceof Promise)
				{
					result.then((response) => {
						this.removeFirstAndExecNext();
					})
						.catch((errors) => {
							console.error(errors);
							this.removeFirstAndExecNext();
						});
				}
				else
				{
					this.removeFirstAndExecNext();
				}
			}
		}

		optimizeQueue()
		{
			const hasReloadCommand = this.queue.some((item) => item.eventName === command.RELOAD);
			if (hasReloadCommand)
			{
				this.queue = [
					{
						eventName: command.RELOAD,
						status: queueItemsStatus.WAITING,
					},
				];

				return;
			}

			const deletedItems = [];
			const updatedItems = [];
			const addedItems = [];

			this.queue.forEach((queueItem) => {
				let currentItems = null;
				switch (queueItem.eventName)
				{
					case command.ADDED:
						currentItems = addedItems;
						break;
					case command.UPDATED:
					case command.VIEW:
						currentItems = updatedItems;
						break;
					case command.DELETED:
						currentItems = deletedItems;
						break;
					default:
						currentItems = null;
				}

				if (currentItems)
				{
					queueItem.items.forEach((item) => {
						if (!currentItems.some((element) => element.id === item.id))
						{
							currentItems.push(item);
						}
					});
				}
			});

			if (deletedItems.length > 0 && (addedItems.length > 0 || updatedItems.length > 0))
			{
				deletedItems.forEach((deletedItem) => {
					const indexInUpdated = updatedItems.findIndex((item) => item.id === deletedItem.id);
					if (indexInUpdated > -1)
					{
						updatedItems.splice(indexInUpdated, 1);
					}

					const indexInAdded = addedItems.findIndex((item) => item.id === deletedItem.id);
					if (indexInAdded > -1)
					{
						addedItems.splice(indexInAdded, 1);
					}
				});
			}

			if (addedItems.length > 0 || updatedItems.length > 0)
			{
				addedItems.forEach((addedItem) => {
					const indexInUpdated = updatedItems.findIndex((item) => item.id === addedItem.id);
					if (indexInUpdated > -1)
					{
						updatedItems.splice(indexInUpdated, 1);
					}
				});
			}

			this.queue = [];
			if (deletedItems.length > 0)
			{
				this.queue.push({
					eventName: command.DELETED,
					items: deletedItems,
					status: queueItemsStatus.WAITING,
				});
			}

			if (addedItems.length > 0)
			{
				this.queue.push({
					eventName: command.ADDED,
					items: addedItems,
					status: queueItemsStatus.WAITING,
				});
			}

			if (updatedItems.length > 0)
			{
				this.queue.push({
					eventName: command.UPDATED,
					items: updatedItems,
					status: queueItemsStatus.WAITING,
				});
			}
		}
	}

	module.exports = { PushProcessor, queueItemsStatus };
});
