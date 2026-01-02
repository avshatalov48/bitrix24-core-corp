/**
 * @module im/messenger-v2/controller/recent/service/database-load/common
 */
jn.define('im/messenger-v2/controller/recent/service/database-load/common', (require, exports, module) => {
	const { Type } = require('type');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	const { PaginationState } = require('im/messenger-v2/controller/recent/service/pagination/lib/state');
	const { BaseRecentService } = require('im/messenger-v2/controller/recent/service/base');

	/**
	 * @implements {IDatabaseLoadService}
	 * @class CommonDatabaseLoadService
	 */
	class CommonDatabaseLoadService extends BaseRecentService
	{
		onInit()
		{
			this.logger.log('onInit');

			this.core = serviceLocator.get('core');
			this.store = serviceLocator.get('core').getStore();
			this.recentRepository = this.core.getRepository().recent;

			this.hasMore = true;
			this.setLastItem(null);
		}

		/**
		 * @param {object|null} lastItem
		 */
		setLastItem(lastItem)
		{
			this.lastItem = lastItem;
		}

		/**
		 * @return {Promise<LoadedPageCursor>}
		 */
		async loadNextPage()
		{
			this.logger.log('loadNextPage');

			const page = await this.#getPage();
			await this.#savePageToModel(page);

			this.logger.log('loadNextPage complete');

			return this.#processLoadedPageData(page);
		}

		/**
		 * @return {Promise<LoadedPageCursor>}
		 */
		async loadFirstPage()
		{
			this.logger.log('loadFirstPage');

			const page = await this.#getFirstPage();
			await this.#savePageToModel(page, true);

			const loadedPageCursor = this.#processLoadedPageData(page);

			this.#setPaginationState(loadedPageCursor);

			this.logger.log('loadFirstPage complete');
		}

		/**
		 * @private
		 * @return ListByDialogTypeFilter
		 */
		get filter()
		{
			return this.props.filter;
		}

		/**
		 * @private
		 * @return {string}
		 */
		get savePageAction()
		{
			return this.props.savePageAction;
		}

		/**
		 * @private
		 * @return {string}
		 */
		get saveFirstPageAction()
		{
			return 'recentModel/setFirstPageByTab';
		}

		/**
		 * @private
		 * @return {string|null}
		 */
		get lastActivityDate()
		{
			return this.lastItem?.lastActivityDate;
		}

		/**
		 * @returns {IRenderService|null}
		 */
		get renderService()
		{
			return this.recentLocator.get('render') ?? null;
		}

		/**
		 * @returns {IPaginationService|null}
		 */
		get paginationService()
		{
			return this.recentLocator.get('pagination') ?? null;
		}

		/**
		 * @return {Promise<RecentListResult>}
		 */
		async #getFirstPage()
		{
			const filter = this.filter;
			this.logger.log('#getFirstPage filter:', filter);
			const page = await this.recentRepository.getListByDialogTypeFilter(filter);
			this.logger.log('#getFirstPage loaded:', page);

			return page;
		}

		/**
		 * @return {Promise<RecentListResult>}
		 */
		async #getPage()
		{
			const filter = { ...this.filter, lastActivityDate: this.lastActivityDate };
			this.logger.log('#getPage filter:', filter);
			const page = await this.recentRepository.getListByDialogTypeFilter(filter);
			this.logger.log('#getPage loaded:', page);

			return page;
		}

		/**
		 * @param {RecentListResult} page
		 * @return {LoadedPageCursor}
		 */
		#processLoadedPageData(page)
		{
			const lastActivityDate = this.#extractLastActivityDateFromPage(page);
			this.setLastItem({
				lastActivityDate,
			});

			this.logger.log('#processLoadedPage lastActivityDate:', lastActivityDate);

			this.#manageLoader(page.hasMore);

			return {
				hasMore: page.hasMore,
				lastItem: this.lastItem,
			};
		}

		/**
		 * @param {RecentListResult} page
		 * @param {boolean} firstPage
		 */
		async #savePageToModel(page, firstPage = false)
		{
			try
			{
				const savePromiseList = [];
				const chatList = page.items.map((item) => item.chat);
				if (Type.isArrayFilled(chatList))
				{
					savePromiseList.push(this.store.dispatch('dialoguesModel/setCollectionFromLocalDatabase', chatList));
				}

				if (Type.isArrayFilled(page.users))
				{
					savePromiseList.push(this.store.dispatch('usersModel/setFromLocalDatabase', page.users));
				}

				if (Type.isArrayFilled(page.messages))
				{
					savePromiseList.push(this.store.dispatch('messagesModel/store', page.messages));
				}

				if (Type.isArrayFilled(page.files))
				{
					savePromiseList.push(this.store.dispatch('filesModel/setFromLocalDatabase', page.files));
				}

				if (Type.isArrayFilled(page.draft))
				{
					savePromiseList.push(this.store.dispatch('draftModel/setFromLocalDatabase', page.draft));
				}

				await Promise.all(savePromiseList);

				if (Type.isArrayFilled(page.items))
				{
					const recentAction = firstPage ? this.saveFirstPageAction : this.savePageAction;
					await this.store.dispatch(recentAction, { tab: this.recentLocator.get('id'), itemList: page.items });
				}
			}
			catch (error)
			{
				this.logger.error('saveFirstPageToModel error:', error);
			}
		}

		/**
		 * @param {RecentListResult} page
		 * @return {string|null}
		 */
		#extractLastActivityDateFromPage(page)
		{
			if (!page.items || page.items.length === 0)
			{
				return null;
			}

			const lastActivityDateObj = page.items[page.items.length - 1]?.lastActivityDate;

			return lastActivityDateObj?.toISOString() ?? null;
		}

		/**
		 * @param {boolean} hasMore
		 */
		#manageLoader(hasMore)
		{
			if (hasMore)
			{
				this.renderService?.showLoader();
			}
			else
			{
				this.renderService?.hideLoader();
			}
		}

		/**
		 * @param {LoadedPageCursor} loadedPageCursor
		 */
		#setPaginationState(loadedPageCursor)
		{
			this.paginationService?.setFirstPageData(
				PaginationState.DATABASE_SOURCE,
				{
					hasMore: loadedPageCursor.hasMore,
					nextPage: 2,
					lastItem: loadedPageCursor.lastItem,
				},
			);
		}
	}

	module.exports = CommonDatabaseLoadService;
});
