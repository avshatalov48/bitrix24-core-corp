/**
 * @module im/messenger/controller/search/experimental/selector
 */
jn.define('im/messenger/controller/search/experimental/selector', (require, exports, module) => {
	const { EventType } = require('im/messenger/const');
	const { Loc } = require('loc');
	const { RecentProvider } = require('im/messenger/controller/search/experimental/provider');
	const { SearchConverter } = require('im/messenger/lib/converter/search');
	const { Logger } = require('im/messenger/lib/logger');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { formatDateByDialogId } = require('im/messenger/controller/search/experimental/helper/search-date-formatter');

	const CAROUSEL_SECTION_CODE = 'custom';
	const RECENT_SECTION_CODE = 'recent';
	const COMMON_SECTION_CODE = 'common';

	class RecentSelector
	{
		/**
		 *
		 * @param {JNBaseList} ui
		 */
		constructor(ui)
		{
			this.store = serviceLocator.get('core').getStore();
			this.ui = ui;
			this.isOpen = false;
			this.sections = {};
			/**
			 * @protected
			 * @type {RecentProvider}
			 */
			this.provider = null;

			this.isRecentLoading = false;
			this.recentItems = [];

			this.showSearchItem = false;

			this.processedQuery = '';

			this.onScopeSelectedHandler = this.onScopeSelected.bind(this);
			this.onUserTypeTextHandler = this.onUserTypeText.bind(this);
			this.onSearchItemSelectedHandler = this.onSearchItemSelected.bind(this);
			this.searchSectionButtonClickHandler = this.searchSectionButtonClick.bind(this);

			this.initProvider();
			this.subscribeEvents();
			this.setSections();
		}

		open()
		{
			this.loadRecentSearchFromServer();
			this.isOpen = true;
			this.drawRecent(this.recentItems);
		}

		/**
		 * @private
		 */
		subscribeEvents()
		{
			this.ui.on(EventType.recent.scopeSelected, this.onScopeSelectedHandler);
			this.ui.on(EventType.recent.userTypeText, this.onUserTypeTextHandler);
			this.ui.on(EventType.recent.searchItemSelected, this.onSearchItemSelectedHandler);
			this.ui.on(EventType.recent.searchSectionButtonClick, this.searchSectionButtonClickHandler);
		}

		unsubscribeEvents()
		{
			this.ui.off(EventType.recent.scopeSelected, this.onScopeSelectedHandler);
			this.ui.off(EventType.recent.userTypeText, this.onUserTypeTextHandler);
			this.ui.off(EventType.recent.searchItemSelected, this.onSearchItemSelectedHandler);
			this.ui.off(EventType.recent.searchSectionButtonClick, this.searchSectionButtonClickHandler);
		}

		/**
		 * @protected
		 */
		initProvider()
		{
			this.provider = new RecentProvider({
				loadLatestSearchProcessed: () => {
					Logger.log('RecentSelector.loadLatestSearchProcessed');
					this.isRecentLoading = true;
				},
				loadLatestSearchComplete: (recentIds) => {
					Logger.log('RecentSelector.loadLatestSearchComplete', recentIds);
					this.isRecentLoading = false;
					this.recentItems = recentIds;
					this.drawRecent(this.recentItems);
				},
				loadSearchProcessed: (localSearchIds, isStartServerSearch) => {
					Logger.log('RecentSelector.loadSearchProcessed', localSearchIds, isStartServerSearch);
					this.showSearchItem = isStartServerSearch;

					this.drawSearch(localSearchIds);
				},
				loadSearchComplete: (searchIds, query) => {
					Logger.log('RecentSelector.loadSearchComplete', searchIds, query);

					if (query !== this.processedQuery)
					{
						Logger.warn('RecentSelector.loadSearchComplete: incoming query not equal processed, dont need redraw');

						return;
					}

					this.showSearchItem = false;
					this.drawSearch(searchIds);
				},
			});
		}

		/**
		 * @param {Array<string>}recentIds
		 */
		drawRecent(recentIds)
		{
			if (this.processedQuery !== '')
			{
				Logger.warn('RecentSelector.loadLatestSearchComplete: search is progress, dont need draw latest search result');

				return;
			}
			const result = [];
			result.push(this.getCarouselItem());
			recentIds.forEach((recentId) => {
				const item = this.prepareItemForDrawing(recentId, RECENT_SECTION_CODE);

				if (!item)
				{
					Logger.error('RecentSelector.drawRecent: unknown chat or user id', recentId);

					return;
				}

				result.push(item);
			});

			if (this.isRecentLoading)
			{
				result.push(this.getLoadingItem());
			}
			Logger.log('RecentSelector.drawRecent:', result);

			this.drawItems(result);
		}

		drawSearch(searchIds)
		{
			const result = [];
			searchIds.forEach((searchId) => {
				const item = this.prepareItemForDrawing(searchId, COMMON_SECTION_CODE);

				if (!item)
				{
					Logger.error('RecentSelector.drawSearch: unknown chat or user id', searchId);
				}
				item.displayedDate = formatDateByDialogId(searchId);

				result.push(item);
			});

			if (this.showSearchItem)
			{
				result.push(this.getSearchingItem());
			}

			if (result.length === 0)
			{
				result.push(this.getEmptyItem());
			}

			this.drawItems(result);
		}

		/**
		 * @private
		 */
		drawItems(items)
		{
			if (!this.isOpen)
			{
				return;
			}

			const currentSections = new Map();
			items.forEach((item) => {
				if (!currentSections.has(item.sectionCode))
				{
					currentSections.set(item.sectionCode, this.sections[item.sectionCode]);
				}
			});

			this.ui.setSearchResultItems(items, [...currentSections.values()]);
		}

		/**
		 * @private
		 * @param itemId
		 * @param sectionCode
		 * @return {object || null}
		 */
		prepareItemForDrawing(itemId, sectionCode)
		{
			if (DialogHelper.isChatId(itemId))
			{
				const userModel = this.store.getters['usersModel/getById'](Number(itemId));

				if (!userModel)
				{
					return null;
				}

				return SearchConverter.toUserSearchItem(userModel, sectionCode);
			}

			if (DialogHelper.isDialogId(itemId))
			{
				const dialogModel = this.store.getters['dialoguesModel/getById'](itemId);

				if (!dialogModel)
				{
					return null;
				}

				return SearchConverter.toDialogSearchItem(dialogModel, sectionCode);
			}

			return null;
		}

		/**
		 * @private
		 */
		setSections()
		{
			this.sections = {
				[CAROUSEL_SECTION_CODE]: {
					id: CAROUSEL_SECTION_CODE,
					title: Loc.getMessage('IMMOBILE_SEARCH_EXPERIMENTAL_RECENT_USERS_SECTION').toUpperCase(),
					backgroundColor: '#f6f7f8',
				},
				[RECENT_SECTION_CODE]: {
					id: RECENT_SECTION_CODE,
					backgroundColor: '#f6f7f8',
					title: Loc.getMessage('IMMOBILE_SEARCH_EXPERIMENTAL_RECENT_SECTION').toUpperCase(),
				},
				[COMMON_SECTION_CODE]: {
					id: COMMON_SECTION_CODE,
				},
			};
		}

		/**
		 * @private
		 * @return {{sectionCode: string, hideBottomLine: boolean, childItems: RecentCarouselItem[][], type: string}}
		 */
		getCarouselItem()
		{
			const carouselUserItems = this.provider.loadRecentUsers()
				.map((userId) => {
					const user = this.store.getters['usersModel/getById'](userId);

					return SearchConverter.toUserCarouselItem(user);
				})
			;

			return {
				type: 'carousel',
				sectionCode: CAROUSEL_SECTION_CODE,
				hideBottomLine: true,
				childItems: carouselUserItems,
			};
		}

		/**
		 * @private
		 * @return {{unselectable: boolean, sectionCode: string, id: string, title: string, type: string}}
		 */
		getLoadingItem()
		{
			return {
				id: 'loading',
				title: Loc.getMessage('IMMOBILE_SEARCH_EXPERIMENTAL_LOADING_ITEM'),
				type: 'loading',
				unselectable: true,
				sectionCode: COMMON_SECTION_CODE,
			};
		}

		/**
		 * @private
		 * @return {{unselectable: boolean, sectionCode: string, id: string, title: string, type: string}}
		 */
		getSearchingItem()
		{
			return {
				id: 'loading',
				title: Loc.getMessage('IMMOBILE_SEARCH_EXPERIMENTAL_SEARCHING_ITEM'),
				type: 'loading',
				unselectable: true,
				sectionCode: COMMON_SECTION_CODE,
			};
		}

		getEmptyItem()
		{
			return {
				id: 'empty',
				title: Loc.getMessage('IMMOBILE_SEARCH_EXPERIMENTAL_EMPTY_ITEM'),
				type: 'button',
				unselectable: true,
				sectionCode: COMMON_SECTION_CODE,
			};
		}

		/**
		 * @private
		 */
		loadRecentSearchFromServer()
		{
			void this.provider.loadLatestSearch();
		}

		// region eventHandlers
		/**
		 * @private
		 * @param args
		 */
		onScopeSelected(...args)
		{
			console.log('onScopeSelected', args);
		}

		/**
		 * @private
		 * @param {string} text
		 * @param {string} scope
		 */
		onUserTypeText({ text, scope = '' })
		{
			const currentQuery = this.getClearQuery(text);

			if (currentQuery.length === 0)
			{
				this.processedQuery = '';
				this.drawRecent(this.recentItems);

				return;
			}

			if (currentQuery === this.processedQuery)
			{
				return;
			}

			this.processedQuery = currentQuery;

			void this.provider.doSearch(currentQuery);
		}

		/**
		 * @private
		 * @param {RecentCarouselItem || object} item
		 */
		onSearchItemSelected(item)
		{
			const dialogId = item.params && item.params.id ? item.params.id : null;

			if (dialogId === null)
			{
				return;
			}

			this.provider.saveItemToRecent(dialogId)
				.then(() => {
					this.loadRecentSearchFromServer();
				})
				.catch((error) => {
					Logger.error('RecentSelector.saveItemToRecent', error);
				})
			;

			MessengerEmitter.emit(EventType.messenger.openDialog, { dialogId });
		}

		/**
		 * @private
		 * @param args
		 */
		searchSectionButtonClick(...args)
		{
			console.log('searchSectionButtonClick', args);
		}
		// endregion

		/**
		 * @private
		 * @param {string} text
		 * @return {string}
		 */
		getClearQuery(text)
		{
			return text.trim().toLocaleLowerCase(env.languageId);
		}

		close()
		{
			this.processedQuery = '';
			this.provider.closeSession();
		}
	}

	module.exports = { RecentSelector };
});
