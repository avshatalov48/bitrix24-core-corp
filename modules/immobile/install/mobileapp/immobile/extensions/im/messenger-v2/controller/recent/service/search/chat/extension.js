/**
 * @module im/messenger-v2/controller/recent/service/search/chat
 */
jn.define('im/messenger-v2/controller/recent/service/search/chat', (require, exports, module) => {
	const { RecentSelector } = require('im/messenger/controller/search/experimental');
	const { EventType } = require('im/messenger/const');

	const { BaseUiRecentService } = require('im/messenger-v2/controller/recent/service/base');

	/**
	 * @implements {ISearchService}
	 * @class ChatSearchService
	 */
	class ChatSearchService extends BaseUiRecentService
	{
		onInit()
		{
			this.logger.log('onInit');
		}

		/**
		 * @param {BaseList} ui
		 */
		async onUiReady(ui)
		{
			this.logger.log('onUiReady');

			this.searchSelector = new RecentSelector(ui);

			ui?.on(EventType.recent.searchHide, this.closeSearchHandler.bind(this));
		}

		async openSearch()
		{
			this.logger.log('openSearch');

			try
			{
				await this.uiReadyPromise;
				this.searchSelector.open();
			}
			catch (error)
			{
				this.logger.error('openSearch error: ', error);
			}
		}

		closeSearchHandler()
		{
			this.logger.log('closeSearchHandler');

			try
			{
				this.searchSelector.close();
			}
			catch (error)
			{
				this.logger.error('closeSearchHandler error: ', error);
			}
		}
	}

	module.exports = ChatSearchService;
});
