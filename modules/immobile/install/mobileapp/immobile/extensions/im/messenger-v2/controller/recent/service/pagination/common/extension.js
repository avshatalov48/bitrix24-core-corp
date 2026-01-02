/**
 * @module im/messenger-v2/controller/recent/service/pagination/common
 */
jn.define('im/messenger-v2/controller/recent/service/pagination/common', (require, exports, module) => {
	const { throttle } = require('utils/function');
	const { EventType } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { BaseUiRecentService } = require('im/messenger-v2/controller/recent/service/base');

	const { RecentEventType } = require('im/messenger-v2/controller/recent/const');
	const { PaginationState } = require('im/messenger-v2/controller/recent/service/pagination/lib/state');

	/**
	 * @implements {IPaginationService}
	 * @class CommonPaginationService
	 */
	class CommonPaginationService extends BaseUiRecentService
	{
		onInit()
		{
			this.state = new PaginationState();
			this.loadNextPageItemId = 'loadNextPage';
			if (!this.recentLocator.has('database-load'))
			{
				this.state.disable(PaginationState.DATABASE_SOURCE);
			}

			if (!this.recentLocator.has('server-load'))
			{
				this.state.disable(PaginationState.SERVER_SOURCE);
			}
		}

		async onUiReady(ui)
		{
			this.logger.warn('onUiReady');
			/** @type {BaseList} */
			this.ui = ui;
			ui.on(RecentEventType.ui.onScroll, this.scrollHandler);
			ui.on(RecentEventType.ui.itemWillDisplay, this.itemWillDisplayHandler);
			ui.on(EventType.recent.refresh, this.refreshHandler);
		}

		/**
		 * @param {PaginationState[DATABASE_SOURCE] | PaginationState[SERVER_SOURCE]} source
		 * @param {boolean} hasMore
		 * @param {number} nextPage
		 * @param {object} lastItem
		 */
		setFirstPageData(source, { hasMore, nextPage, lastItem })
		{
			this.state.update(source, { hasMore, nextPage, lastItem });
			if (this.recentLocator.has('database-load'))
			{
				this.recentLocator.get('database-load').setLastItem(lastItem);
			}
		}

		/**
		 * @param {PaginationState[DATABASE_SOURCE] | PaginationState[SERVER_SOURCE]} source
		 * @return {RecentItemData|null}
		 */
		getLastItem(source)
		{
			return this.state.getLastItem(source);
		}

		scrollHandler = throttle((event) => {
			if (event.offset.y >= event.contentSize.height * 0.8)
			{
				this.logger.warn('scrollHandler try load next page', event);
				this.#loadNextPage()
					.catch((error) => {
						this.logger.error('scrollHandler error', error);
					})
				;
			}
		}, 50, this);

		/**
		 * @param {{id: string}} item
		 */
		itemWillDisplayHandler = (item) => {
			if (item.id.startsWith(this.loadNextPageItemId))
			{
				this.logger.warn('itemWillDisplayHandler try load next page', item);
				this.#loadNextPage()
					.catch((error) => {
						this.logger.error('itemWillDisplayHandler error', error);
					})
				;
			}
		};

		refreshHandler = async () => {
			try
			{
				if (!serviceLocator.has('refresher'))
				{
					return;
				}

				await serviceLocator.get('refresher').refreshOnScrollUp();
			}
			catch (error)
			{
				this.logger.error('refreshHandler refreshOnScrollUp error', error);
			}
			finally
			{
				this.ui.stopRefreshing();
			}
		};

		#shouldLoad()
		{
			return this.state.hasMore() && !this.state.isLoading();
		}

		async #loadNextPage()
		{
			if (!this.#shouldLoad())
			{
				this.logger.log('loadNextPage: should not load', this.state);

				return;
			}

			if (this.state.canLoad(PaginationState.DATABASE_SOURCE))
			{
				await this.#loadFromDatabase();
			}

			if (this.state.canLoad(PaginationState.SERVER_SOURCE))
			{
				await this.#loadFromServer();
			}
		}

		async #loadFromDatabase()
		{
			this.state.startLoading(PaginationState.DATABASE_SOURCE);

			try
			{
				const loader = this.recentLocator.get('database-load');
				const result = await loader.loadNextPage();
				this.state.update(PaginationState.DATABASE_SOURCE, result);
			}
			catch (error)
			{
				this.logger.error('loadFromDatabase error:', error);
				this.state.markAsFailed(PaginationState.DATABASE_SOURCE);
			}
		}

		async #loadFromServer()
		{
			if (this.state.isLoading(PaginationState.DATABASE_SOURCE))
			{
				return;
			}

			this.state.startLoading(PaginationState.SERVER_SOURCE);

			try
			{
				const loader = this.recentLocator.get('server-load');
				const result = await loader.loadNextPage();
				this.state.update(PaginationState.SERVER_SOURCE, result);
			}
			catch (error)
			{
				this.logger.error('Server load failed:', error);
				this.state.markAsFailed(PaginationState.SERVER_SOURCE);
			}
		}
	}

	module.exports = CommonPaginationService;
});
