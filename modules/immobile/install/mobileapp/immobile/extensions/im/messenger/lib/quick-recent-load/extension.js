/**
 * @module im/messenger/lib/quick-recent-load
 */
jn.define('im/messenger/lib/quick-recent-load', (require, exports, module) => {
	/* global dialogList */
	const { RecentViewCache } = require('im/messenger/cache');
	const { getLogger } = require('im/messenger/lib/logger');
	const { MessengerParams } = require('im/messenger/lib/params');
	const logger = getLogger('recent--quick-load');

	/**
	 * @class QuickRecentLoader
	 */
	class QuickRecentLoader
	{
		#ui = null;

		constructor(ui, cacheName = this.getCacheName())
		{
			this.#ui = ui;

			/** @typedef {RecentViewCache}  */
			this.cache = new RecentViewCache(cacheName);
		}

		getCacheName()
		{
			return `${MessengerParams.getComponentCode() || 'im.messenger'}/recent`;
		}

		/**
		 * @void
		 */
		static renderItemsOnViewLoaded(ui)
		{
			logger.warn('QuickRecentLoader.renderCachedItems');

			new QuickRecentLoader(ui).renderWhenViewIsReady();
		}

		/**
		 * @param {Array<JNListWidgetSectionItem>} sections
		 * @param {Array<RecentWidgetItem|RecentItem>} items
		 */
		saveCache(sections, items)
		{
			this.cache.save(sections, items);
		}

		/**
		 * @void
		 */
		renderWhenViewIsReady()
		{
			const cache = this.cache.get();

			BX.onViewLoaded(() => {
				if (cache)
				{
					this.renderCachedItems(cache);
				}
			});
		}

		/**
		 * @param {RecentViewCacheData|boolean} [cacheData]
		 * @void
		 */
		renderCachedItems(cacheData = this.cache.get())
		{
			if (!cacheData)
			{
				return;
			}

			try
			{
				this.#ui.setItems(cacheData.items, cacheData.sections);
				logger.log(`${this.constructor.name}.renderCachedItems dialogList.setItems:`, cacheData.items, cacheData.sections);
			}
			catch (error)
			{
				logger.error(`${this.constructor.name}.renderCachedItems dialogList.setItems.catch:`, error);
			}
		}
	}

	module.exports = {
		QuickRecentLoader,
	};
});
