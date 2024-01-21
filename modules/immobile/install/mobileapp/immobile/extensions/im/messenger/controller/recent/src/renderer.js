/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/controller/recent/renderer
 */
jn.define('im/messenger/controller/recent/renderer', (require, exports, module) => {
	const { Type } = require('type');

	const { RecentConverter } = require('im/messenger/lib/converter');
	const { Worker } = require('im/messenger/lib/helper/worker');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('recent--renderer');

	/**
	 * @class RecentRenderer
	 *
	 * Designed to reduce the number of redraw for RecentView (dialogList).
	 * Collects items to add and modify in update queue and applies at a time.
	 */
	class RecentRenderer
	{
		/**
		 * @param {Object} options
		 * @param {RecentView} options.view
		 */
		constructor(options = {})
		{
			this.ACTION_ADD = 'add';
			this.ACTION_UPDATE = 'update';

			/** @private */
			this.view = options.view;

			/** @private */
			this.updateQueue = {};
			this.resetQueue();

			/** @private */
			this.nextTickCallbackList = [];

			/** @private */
			this.updateWorker = new Worker({
				frequency: 1000,
				callback: this.render.bind(this),
			});

			this.updateWorker.start();
		}

		resetQueue()
		{
			this.getSupportedActions().forEach((actionId) => {
				this.updateQueue[actionId] = {};
			});
		}

		do(action, items)
		{
			if (!this.isActionSupported(action))
			{
				logger.error('RecentRenderer: Unsupported action', action);

				return false;
			}

			items.forEach((item) => {
				this.updateQueue[action][item.id] = item;
			});

			return true;
		}

		add(itemList)
		{
			this.view.addItems(RecentConverter.toList(itemList));
		}

		update(itemList)
		{
			let viewItemList = RecentConverter.toList(itemList);
			viewItemList = viewItemList.map((item) => {
				return {
					filter: { id: item.id.toString() },
					element: item,
				};
			});

			this.view.updateItems(viewItemList);
		}

		removeFromQueue(itemId)
		{
			this.getSupportedActions().forEach((actionId) => {
				delete this.updateQueue[actionId][itemId];
			});
		}

		render()
		{
			const renderStart = Date.now();
			let isViewChanged = false;

			Object.keys(this.updateQueue).forEach((action) => {
				const itemList = [];

				Object.keys(this.updateQueue[action]).forEach((itemId) => {
					itemList.push(this.updateQueue[action][itemId]);

					delete this.updateQueue[action][itemId];
				});

				if (itemList.length > 0)
				{
					isViewChanged = true;

					this[action](itemList);

					logger.info(`RecentRenderer.${action} items:`, itemList);
				}
			});

			if (isViewChanged)
			{
				this.nextTickCallbackList.forEach((callback) => callback());
				this.nextTickCallbackList = [];

				const renderFinish = Date.now();

				logger.info('RecentRenderer.render time:', `${renderFinish - renderStart}ms.`);
			}
		}

		nextTick(callback)
		{
			if (!Type.isFunction(callback))
			{
				throw new TypeError('RecentRenderer.nextTick: callback must be a function');
			}

			this.nextTickCallbackList.push(callback);
		}

		/**
		 * @private
		 */
		getSupportedActions()
		{
			return new Set([
				this.ACTION_ADD,
				this.ACTION_UPDATE,
			]);
		}

		/**
		 * @private
		 */
		isActionSupported(action)
		{
			return this.getSupportedActions().has(action);
		}
	}

	module.exports = {
		RecentRenderer,
	};
});
