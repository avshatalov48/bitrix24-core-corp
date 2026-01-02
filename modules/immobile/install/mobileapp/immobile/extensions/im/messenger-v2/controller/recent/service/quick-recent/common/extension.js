/**
 * @module im/messenger-v2/controller/recent/service/quick-recent/common
 */
jn.define('im/messenger-v2/controller/recent/service/quick-recent/common', (require, exports, module) => {
	const { QuickRecentLoader } = require('im/messenger/lib/quick-recent-load');
	const { BaseUiRecentService } = require('im/messenger-v2/controller/recent/service/base');

	/**
	 * @implements {IQuickRecentService}
	 * @class CommonQuickRecent
	 */
	class CommonQuickRecent extends BaseUiRecentService
	{
		onInit()
		{
			this.logger.log('onInit');
		}

		async onUiReady(ui)
		{
			this.ui = ui;
		}

		/**
		 * @return {Promise<void>}
		 */
		async renderList()
		{
			try
			{
				await this.uiReadyPromise;
				this.#build();
				this.logger.log(`renderList quickRecent for ${this.recentLocator.get('id')} created, try rendering...`);
				this.#render();
			}
			catch (error)
			{
				this.logger.error('renderList catch', error);
			}
		}

		#build()
		{
			this.quickRecent = new QuickRecentLoader(this.ui, `${this.recentLocator.get('id')}/recent`);
		}

		#render()
		{
			this.quickRecent.renderCachedItems();
		}

		/**
		 * @param {Array<JNListWidgetSectionItem>} sections
		 * @param {Array<RecentWidgetItem|RecentItem>} items
		 */
		async save(sections, items)
		{
			this.quickRecent.saveCache(sections, items);
		}
	}

	module.exports = CommonQuickRecent;
});
