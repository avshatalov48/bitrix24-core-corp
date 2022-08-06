/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-pseudo-private */

/**
 * @module im/messenger/lib/renderer/recent
 */
jn.define('im/messenger/lib/renderer/recent', (require, exports, module) => {

	const { Logger } = jn.require('im/messenger/lib/logger');
	const { RecentConverter } = jn.require('im/messenger/lib/converter');
	const { Worker } = jn.require('im/messenger/lib/renderer/worker');

	/**
	 * @class RecentRenderer
	 *
	 * Designed to reduce the number of redraw for RecentView (dialogList).
	 * Collects items to add and modify in update queue and applies at a time.
	 *
	 * @property {RecentView} _view
	 * @property {Object} _updateQueue
	 * @property {Worker} _updateWorker
	 * @property {Worker} _listUpdateWorker
	 */
	class RecentRenderer
	{
		/**
		 * @param {Object} options
		 * @param {RecentView} options.view
		 */
		constructor(options = {})
		{
			this._view = options.view;

			this.ACTION_ADD = 'add';
			this.ACTION_UPDATE = 'update';

			this._updateQueue = {};
			this.resetQueue();

			this._updateWorker = new Worker({
				frequency: 1000,
				callback: this.render.bind(this),
			});

			this._updateWorker.start();
		}

		resetQueue()
		{
			this._getSupportedActions().forEach((actionId) => {
				this._updateQueue[actionId] = {};
			});
		}

		do(action, items)
		{
			if (!this._isActionSupported(action))
			{
				Logger.error('RecentRenderer: Unsupported action', action);

				return false;
			}

			items.forEach((item) => {
				this._updateQueue[action][item.id] = item;
			});

			return true;
		}

		add(itemList)
		{
			this._view.addItems(RecentConverter.toList(itemList));
		}

		update(itemList)
		{
			itemList = itemList.map(item => {
				return {
					filter: { id: item.id.toString() },
					element: RecentConverter.toListItem(item),
				};
			});

			this._view.updateItems(itemList);
		}

		removeFromQueue(itemId)
		{
			this._getSupportedActions().forEach((actionId) => {
				delete this._updateQueue[actionId][itemId];
			});
		}

		render()
		{
			const renderStart = Date.now();
			let isViewChanged = false;

			Object.keys(this._updateQueue).forEach((action) => {
				const itemList = [];

				Object.keys(this._updateQueue[action]).forEach(itemId => {
					itemList.push(this._updateQueue[action][itemId]);

					delete this._updateQueue[action][itemId];
				});

				if (itemList.length > 0)
				{
					isViewChanged = true;

					this[action](itemList);

					Logger.info('RecentRenderer.' + action + ' items:', itemList);
				}
			});

			if (isViewChanged)
			{
				const renderFinish = Date.now();

				Logger.info('RecentRenderer.render time:', renderFinish - renderStart + 'ms.');
			}
		}

		_getSupportedActions()
		{
			return new Set([
				this.ACTION_ADD,
				this.ACTION_UPDATE,
			]);
		}

		_isActionSupported(action)
		{
			return this._getSupportedActions().has(action);
		}
	}

	module.exports = {
		RecentRenderer,
	};
});
