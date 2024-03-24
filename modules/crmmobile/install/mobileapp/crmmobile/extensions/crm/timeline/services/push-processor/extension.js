/**
 * @module crm/timeline/services/push-processor
 */
jn.define('crm/timeline/services/push-processor', (require, exports, module) => {
	const { TimelineStreamScheduled } = require('crm/timeline/stream');
	const { clone } = require('utils/object');

	const StreamNames = {
		PINNED: 'pinned',
		PINNED_ALIAS: 'fixedHistory',
		SCHEDULED: 'scheduled',
		HISTORY: 'history',
	};

	/**
	 * @class TimelinePushProcessor
	 */
	class TimelinePushProcessor
	{
		/**
		 * @param {Timeline} timeline
		 * @param {Function|null} onStreamChanged
		 */
		constructor({ timeline, onStreamChanged })
		{
			/** @type {Timeline} */
			this.timelineInstance = timeline;

			/** @type {Function|null} */
			this.onStreamChangedHandler = onStreamChanged;

			/** @type {TimelinePushActionParams[]} */
			this.queue = [];

			/** @type {boolean} */
			this.queueProcessingInProgress = false;

			/** @type {TimelinePushActionParams[]} */
			this.reloadingMessagesQueue = [];

			this.fetchItems = this.delay(this.fetchItems, 1500);
		}

		/**
		 * @public
		 * @param {TimelinePushActionParams} params
		 */
		handleMessage(params)
		{
			if (this.itemDataShouldBeReloaded(params))
			{
				this.reloadingMessagesQueue.push(params);
				this.fetchItems();
			}
			else
			{
				this.addToQueue(params);
			}
		}

		/**
		 * @param {TimelinePushActionParams} params
		 * @return {boolean}
		 */
		itemDataShouldBeReloaded(params)
		{
			const { item } = params;

			if (!item)
			{
				return false;
			}

			const canBeReloaded = BX.prop.getBoolean(item, 'canBeReloaded', true);
			if (!canBeReloaded)
			{
				return false;
			}

			const appLanguage = env.languageId.toLowerCase();
			const languageId = BX.prop.getString(item, 'languageId', appLanguage).toLowerCase();
			if (languageId !== appLanguage)
			{
				return true;
			}

			const userId = Number(env.userId.toString());
			const targetUsersList = BX.prop.getArray(item, 'targetUsersList', []);
			const isForCurrentUser = targetUsersList.includes(userId) || targetUsersList.length === 0;

			return !isForCurrentUser;
		}

		fetchItems()
		{
			const messages = clone(this.reloadingMessagesQueue);
			this.reloadingMessagesQueue = [];

			const activityIds = [];
			const historyIds = [];

			messages.forEach((message) => {
				const container = message.stream === StreamNames.SCHEDULED ? activityIds : historyIds;
				container.push(message.id);
			});

			if (messages.length > 0)
			{
				this.timelineInstance.dataProvider.loadItems(activityIds, historyIds)
					.then((response) => {
						messages.forEach((message) => {
							if (response.data[message.id])
							{
								message.item = response.data[message.id];
							}
							this.addToQueue(message);
						});
					})
					.catch((err) => {
						console.error(err);
						messages.forEach((message) => this.addToQueue(message));
					});
			}
		}

		addToQueue(params)
		{
			this.queue.push(params);

			if (this.queueProcessingInProgress)
			{
				return;
			}

			this.processNextQueueItem();
		}

		processNextQueueItem()
		{
			if (this.queue.length === 0)
			{
				this.queueProcessingInProgress = false;

				return;
			}

			this.queueProcessingInProgress = true;
			const params = this.queue.shift();
			const results = [];

			switch (params.action)
			{
				case 'add':
					results.push(this.addItem(params.id, params.item, params.stream));
					break;

				case 'update':
					results.push(this.updateItem(params.id, params.item, params.stream));
					if (params.stream === StreamNames.HISTORY)
					{
						results.push(this.updateItem(params.id, params.item, StreamNames.PINNED));
					}
					break;

				case 'delete':
					results.push(this.deleteItem(params.id, params.stream));
					if (params.stream === StreamNames.HISTORY)
					{
						results.push(this.deleteItem(params.id, StreamNames.PINNED));
					}
					break;

				case 'move':
					const source = {
						itemId: params.params.fromId,
						streamName: params.params.fromStream,
					};
					const destination = {
						itemId: params.id,
						streamName: params.stream,
						itemData: params.item,
					};
					results.push(this.moveItem(source, destination));
					break;

				case 'changePinned':
					if (params.params.fromStream === StreamNames.HISTORY)
					{
						results.push(this.pinItem(params.id, params.item));
					}
					else
					{
						results.push(this.unpinItem(params.id, params.item));
					}
					break;
			}

			Promise.all(results)
				.catch((err) => console.error(err))
				.finally(() => this.processNextQueueItem());
		}

		/**
		 * @private
		 * @param {string|number} itemId
		 * @param {object} itemData
		 * @param {string} streamName
		 * @return {Promise}
		 */
		addItem(itemId, itemData, streamName)
		{
			return this.withStream(streamName, (stream) => {
				if (stream.hasItem(itemId))
				{
					return Promise.resolve();
				}

				return stream.addItem(itemData).then(() => {
					this.onStreamChanged({ stream, itemId });
				});
			});
		}

		/**
		 * @private
		 * @param {string|number} itemId
		 * @param {object} itemData
		 * @param {string} streamName
		 * @param {boolean} animated
		 * @return {Promise}
		 */
		updateItem(itemId, itemData, streamName, animated = true)
		{
			return this.withStream(streamName, (stream) => {
				return stream.updateItem(itemId, itemData, animated).then(() => {
					this.onStreamChanged({ stream, itemId });
				});
			});
		}

		/**
		 * @private
		 * @param {string|number} itemId
		 * @param {string} streamName
		 */
		deleteItem(itemId, streamName)
		{
			return this.withStream(streamName, (stream) => {
				return stream.deleteItem(itemId).then(() => {
					this.onStreamChanged({ stream, itemId });
				});
			});
		}

		/**
		 * @private
		 * @param {object} source
		 * @param {object} destination
		 * @return {Promise}
		 */
		moveItem(source, destination)
		{
			return this.deleteItem(source.itemId, source.streamName)
				.then(() => this.addItem(destination.itemId, destination.itemData, destination.streamName));
		}

		/**
		 * @private
		 * @param {string|number} itemId
		 * @param {object} itemData
		 * @return {Promise}
		 */
		pinItem(itemId, itemData)
		{
			return this.withStream(StreamNames.PINNED, (pinnedStream) => {
				if (pinnedStream.hasItem(itemId))
				{
					return Promise.resolve();
				}

				return this.updateItem(itemId, itemData, StreamNames.HISTORY, false)
					.then(() => this.addItem(itemId, itemData, StreamNames.PINNED));
			});
		}

		/**
		 * @private
		 * @param {string|number} itemId
		 * @param {object} itemData
		 * @return {Promise}
		 */
		unpinItem(itemId, itemData)
		{
			return this.deleteItem(itemId, StreamNames.PINNED)
				.then(() => this.updateItem(itemId, itemData, StreamNames.HISTORY, false));
		}

		/**
		 * @private
		 * @param {string} name
		 * @return {TimelineStreamBase|null}
		 */
		getStream(name)
		{
			switch (name)
			{
				case StreamNames.HISTORY:
					return this.timelineInstance.historyStream;

				case StreamNames.SCHEDULED:
					return this.timelineInstance.scheduledStream;

				case StreamNames.PINNED:
				case StreamNames.PINNED_ALIAS:
					return this.timelineInstance.pinnedStream;

				default:
					throw new Error(`Unknown stream ${name}`);
			}
		}

		/**
		 * @private
		 * @param {string} name
		 * @param {function(TimelineStreamBase):Promise} fn
		 * @return {Promise}
		 */
		withStream(name, fn)
		{
			const stream = this.getStream(name);

			return stream ? fn(stream) : Promise.resolve();
		}

		/**
		 * @private
		 * @param {TimelineStreamBase} stream
		 * @param {string|number} itemId
		 */
		onStreamChanged({ stream, itemId })
		{
			if (stream instanceof TimelineStreamScheduled)
			{
				this.timelineInstance.emitTabCounterChange();
			}

			if (this.onStreamChangedHandler)
			{
				this.onStreamChangedHandler({ stream, itemId });
			}
		}

		/**
		 * @private
		 * @param {function} fn
		 * @param {number} timeout
		 * @return {function}
		 */
		delay(fn, timeout)
		{
			const context = this;

			return function()
			{
				setTimeout(() => fn.apply(context, arguments), timeout);
			};
		}
	}

	module.exports = { TimelinePushProcessor };
});
