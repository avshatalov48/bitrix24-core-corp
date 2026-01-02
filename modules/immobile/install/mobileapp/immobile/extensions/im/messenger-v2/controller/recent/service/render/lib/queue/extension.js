/**
 * @module im/messenger-v2/controller/recent/service/render/lib/queue
 */
jn.define('im/messenger-v2/controller/recent/service/render/lib/queue', (require, exports, module) => {
	const QueueOperation = {
		setItems: 'setItems',
		setPreparedItems: 'setPreparedItems',
		removeItems: 'removeItems',
		removeItemsByIds: 'removeItemsByIds',
		upsertItems: 'upsertItems',
		upsertPreparedItems: 'upsertPreparedItems',
	};

	/**
	 * @class RenderQueue
	 */
	class RenderQueue
	{
		constructor()
		{
			/**
			 * @type {Array<RecentQueueItem>}
			 */
			this.queue = [];
		}

		/**
		 * @return {Array<RecentQueueItem>}
		 */
		getValues()
		{
			return this.queue;
		}

		reset()
		{
			this.queue = [];
		}

		/**
		 * @param {Array<RecentModelState>} items
		 * @param {object} [options]
		 */
		setItems(items, options = {})
		{
			this.queue = [{
				operation: QueueOperation.setItems,
				items,
				options,
			}];
		}

		/**
		 * @param {Array<RecentItem>} preparedItems
		 * @param {object} [options]
		 */
		setPreparedItems(preparedItems, options = {})
		{
			this.queue = [{
				operation: QueueOperation.setPreparedItems,
				preparedItems,
				options,
			}];
		}

		/**
		 * @param {Array<RecentModelState>} items
		 * @param {object} [options]
		 */
		upsertItems(items, options = {})
		{
			this.queue.push({
				operation: QueueOperation.upsertItems,
				items,
				options,
			});
		}

		/**
		 * @param {Array<RecentItem>} preparedItems
		 * @param {object} [options]
		 */
		upsertPreparedItems(preparedItems, options = {})
		{
			this.queue.push({
				operation: QueueOperation.upsertPreparedItems,
				preparedItems,
				options,
			});
		}

		/**
		 * @param {Array<RecentModelState>|Array<{id:string}>} items
		 * @param {object} [options]
		 */
		removeItems(items, options = {})
		{
			const itemIdSet = new Set(items.map((item) => String(item.id)));
			this.removeItemFromQueue(itemIdSet);

			this.queue.push({
				operation: QueueOperation.removeItems,
				items,
				options,
			});
		}

		/**
		 * @param {Array<string>} ids
		 * @param {object} [options]
		 */
		removeItemsByIds(ids, options = {})
		{
			const itemIdSet = new Set(ids.map((item) => String(item)));
			this.removeItemFromQueue(itemIdSet);

			this.queue.push({
				operation: QueueOperation.removeItemsByIds,
				ids,
				options,
			});
		}

		/**
		 * @param {Set<string>}itemIdSet
		 * @return {Set<string>}
		 */
		removeItemFromQueue(itemIdSet)
		{
			/**
			 * @type {Set<string>}
			 */
			const deletedItemIds = new Set();
			for (const recentQueueItem of this.queue)
			{
				if (recentQueueItem.operation === 'removeItems' || recentQueueItem.operation === 'removeItemsByIds')
				{
					continue;
				}

				if (recentQueueItem.items)
				{
					recentQueueItem.items = recentQueueItem.items.filter((item) => {
						const id = String(item.id);

						if (itemIdSet.has(id))
						{
							deletedItemIds.add(id);

							return false;
						}

						return true;
					});
				}

				if (recentQueueItem.preparedItems)
				{
					recentQueueItem.preparedItems = recentQueueItem.preparedItems.filter((item) => {
						const id = String(item.id);

						if (itemIdSet.has(id))
						{
							deletedItemIds.add(id);

							return false;
						}

						return true;
					});
				}
			}

			/**
			 * @type {Set<string>}
			 */
			const result = new Set(itemIdSet);
			for (const deletedItemId of deletedItemIds)
			{
				result.delete(deletedItemId);
			}

			return result;
		}
	}

	module.exports = { RenderQueue, QueueOperation };
});
