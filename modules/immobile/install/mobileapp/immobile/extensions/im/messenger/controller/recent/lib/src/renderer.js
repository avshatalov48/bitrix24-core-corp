/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/controller/recent/lib/renderer
 */
jn.define('im/messenger/controller/recent/lib/renderer', (require, exports, module) => {
	const { Type } = require('type');
	const { isEqual } = require('utils/object');
	const { uniqBy } = require('utils/array');

	const { Feature } = require('im/messenger/lib/feature');
	const { RecentUiConverter } = require('im/messenger/lib/converter/ui/recent');
	const { Worker } = require('im/messenger/lib/helper/worker');
	const { getLogger } = require('im/messenger/lib/logger');
	const logger = getLogger('recent--renderer');

	/**
	 * @class RecentRenderer
	 *
	 * Designed to reduce the number of redrawing for RecentView (dialogList).
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
			this.ACTION_ADD_PREPARED_ITEMS = 'addPreparedItems';
			this.ACTION_UPDATE = 'update';
			this.ACTION_UPDATE_PREPARED_ITEMS = 'updatePreparedItems';
			this.ACTION_FIND_ITEM = 'findItem';
			this.ACTION_FIND_ITEM_BY_ID = 'findItemById';
			this.ACTION_REMOVE = 'remove';
			this.ACTION_REMOVE_ITEMS_BY_IDS = 'removeItemsByIds';
			this.ACTION_REMOVE_CALL = 'removeCall';

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

		/**
		 * @param {string} action
		 * @param {Array<object>|object} items
		 * @param {object} [options={}]
		 * @return {boolean}
		 */
		do(action, items, options = {})
		{
			if (!this.isActionSupported(action))
			{
				logger.error('RecentRenderer: Unsupported action', action);

				return false;
			}

			let itemsData = items;
			if (!Array.isArray(items))
			{
				itemsData = [items];
			}

			itemsData.forEach((item) => {
				this.updateQueue[action][item.id] = {
					item,
					...options,
				};
			});

			return true;
		}

		/**
		 * @param {Array<object>} itemList
		 * @return {boolean}
		 */
		add(itemList)
		{
			this.addPreparedItems(RecentUiConverter.toList(itemList));

			return true;
		}

		/**
		 * @param {Array<object>} preparedItems
		 * @return {boolean}
		 */
		addPreparedItems(preparedItems)
		{
			const addItemList = preparedItems.filter((item) => {
				return Type.isStringFilled(item.id) || item.id > 0;
			});

			const itemAddItemList = uniqBy(addItemList, (item) => String(item.id));

			this.view.addItems(itemAddItemList);

			return true;
		}

		/**
		 * @param {Array<object>} itemList
		 * @return {boolean}
		 */
		update(itemList)
		{
			return this.updatePreparedItems(RecentUiConverter.toList(itemList));
		}

		/**
		 * @param {Array<object>} preparedItems
		 * @return {boolean}
		 */
		updatePreparedItems(preparedItems)
		{
			const formattedItems = preparedItems
				.map((item) => ({
					filter: { id: item.id.toString() },
					element: item,
				}))
				.filter((item) => !this.#hasEqualItemInRecent(item.element));

			if (formattedItems.length > 0)
			{
				this.view.updateItems(formattedItems);

				return true;
			}

			return false;
		}

		/**
		 * @param {object} item
		 * @param {(object)=>any} callback
		 * @return {boolean}
		 */
		findItem(item, callback = () => {})
		{
			this.view.findItem({ id: item.id }, callback);

			return true;
		}

		/**
		 * @param {object} item
		 * @param {(object)=>any} callback
		 * @return {Promise<boolean>}
		 */
		async findItemById(item, callback = () => {})
		{
			try
			{
				const recentItem = await this.view.findItemById(item.id);
				callback(recentItem);

				return true;
			}
			catch (error)
			{
				logger.error(`${this.constructor.name}.findItemById`, error);

				return false;
			}
		}

		/**
		 * @param {Array<object>} items
		 * @return {boolean}
		 */
		remove(items)
		{
			items.forEach((item) => {
				this.view.removeItem(item);
			});

			return true;
		}

		/**
		 * @param {Array<object>} items
		 * @return {Promise<boolean>}
		 */
		async removeItemsByIds(items)
		{
			const ids = items.map((item) => item.id);
			await this.view.removeItemsByIds(ids);

			return true;
		}

		/**
		 * @param {Array<object>} items
		 * @return {Promise<boolean>}
		 */
		async removeCall(items)
		{
			if (Feature.isAsyncRecentOperationsAvailable)
			{
				await this.view.removeCallItems(items);
			}
			else
			{
				items.forEach((item) => {
					this.view.removeCallItem(item);
				});
			}

			return true;
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
					const itemData = this.updateQueue[action][itemId];
					const actionsWithCallback = [this.ACTION_FIND_ITEM, this.ACTION_FIND_ITEM_BY_ID];

					if (actionsWithCallback.includes(action) && itemData?.callback)
					{
						this[action]({ id: itemId }, itemData.callback);
					}
					else
					{
						itemList.push(this.updateQueue[action][itemId].item);
					}

					delete this.updateQueue[action][itemId];
				});

				if (itemList.length > 0)
				{
					const result = this[action](itemList);
					// TODO if isViewChanged was true at least once, then we do not change it to false
					isViewChanged = isViewChanged || result;

					if (Type.isBoolean(result) && result === false)
					{
						logger.info(`RecentRenderer.${action} is canceled, the exact same element already exists`);
					}
					else
					{
						logger.info(`RecentRenderer.${action} items:`, itemList);
					}
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
				this.ACTION_ADD_PREPARED_ITEMS,
				this.ACTION_UPDATE,
				this.ACTION_UPDATE_PREPARED_ITEMS,
				this.ACTION_FIND_ITEM,
				this.ACTION_FIND_ITEM_BY_ID,
				this.ACTION_REMOVE,
				this.ACTION_REMOVE_ITEMS_BY_IDS,
				this.ACTION_REMOVE_CALL,
			]);
		}

		/**
		 * @private
		 */
		isActionSupported(action)
		{
			return this.getSupportedActions().has(action);
		}

		/**
		 * @param {RecentItem} item
		 * @returns {boolean}
		 */
		#hasEqualItemInRecent(item)
		{
			const viewItem = this.view.getItem(item?.id.toString());

			return viewItem && isEqual(viewItem, item);
		}

		/**
		 * @desc force stopping worker, used only for dev test
		 * @void
		 */
		stopWorker()
		{
			this.updateWorker.stop();
		}
	}

	module.exports = {
		RecentRenderer,
	};
});
