/**
 * @module im/messenger-v2/controller/navigation/tab-switcher
 */
jn.define('im/messenger-v2/controller/navigation/tab-switcher', (require, exports, module) => {
	const { EventType } = require('im/messenger/const');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { createPromiseWithResolvers } = require('im/messenger/lib/utils');
	const { getLoggerWithContext } = require('im/messenger/lib/logger');
	const { AsyncQueue } = require('im/messenger/lib/utils');
	const { AnalyticsService } = require('im/messenger/provider/services/analytics');

	const { NavigationHelper } = require('im/messenger-v2/controller/navigation/helper');
	const { SelectionTabResult } = require('im/messenger-v2/controller/navigation/result');

	const logger = getLoggerWithContext('tabs--switcher', 'TabSwitcher');

	/**
	 * @class TabSwitcher
	 */
	class TabSwitcher
	{
		#ui = null;
		/** @type {NavigationController} */
		#controller = null;
		/** @type {Partial<TabOptions>} */
		#activatedTabOptions = {};
		#currentTabId = MessengerParams.get('FIRST_TAB_ID', 'chats');
		#previousTabId;
		#queue = new AsyncQueue();
		#selectingTabPromise = Promise.resolve({});
		#selectedTabResolver = () => {};

		/**
		 * @param ui
		 * @param {NavigationController} controller
		 */
		constructor(ui, controller)
		{
			this.#ui = ui;
			this.#controller = controller;
			this.#subscribeEvents();
			this.#sendInitialAnalytics();
		}

		/**
		 * @return {string}
		 */
		async getActiveTab()
		{
			await this.#selectingTabPromise;

			return this.#currentTabId;
		}

		/**
		 * @param  tabId
		 * @param {TabOptions} options
		 * @returns {Promise<SelectionTabResult>}
		 */
		async setActiveTab(tabId, options = {})
		{
			const { promise, resolve } = createPromiseWithResolvers();

			this.#queue.enqueue(async () => {
				const selectionTabResult = await this.#setActiveTab(tabId, options);

				resolve(selectionTabResult);
			});

			return promise;
		}

		async #setActiveTab(tabId, options = {})
		{
			logger.log('setActiveTab', tabId, options);
			if (!NavigationHelper.isTabIdCorrect(tabId))
			{
				return SelectionTabResult.createByIncorrectTabId(tabId);
			}

			if (!NavigationHelper.isTabAvailable(tabId))
			{
				return SelectionTabResult.createByTabIsNotAvailable(tabId);
			}

			await this.#selectingTabPromise;
			const { promise, resolve } = createPromiseWithResolvers();
			this.#selectingTabPromise = promise;
			this.#selectedTabResolver = resolve;

			this.#activatedTabOptions = options;

			this.#ui.setActiveItem(tabId);

			return new SelectionTabResult();
		}

		#tabSelectedHandler = (item, changed) => {
			this.#selectedTabResolver();
			if (!changed)
			{
				logger.log('onTabSelected select active element', this.#currentTabId);

				return;
			}

			if (this.#currentTabId === item.id)
			{
				logger.log('onTabSelected selected tab is equal current, this.currentTab:', this.#currentTabId, item.id);

				return;
			}

			this.#previousTabId = this.#currentTabId;
			this.#currentTabId = item.id;

			this.#sendAnalyticsChangeTab();
			this.#activatedTabOptions = {};

			logger.warn('onTabSelected tabs:', {
				current: this.#currentTabId,
				previous: this.#previousTabId,
			}, item, changed);

			this.#controller.handleTabChanged(this.#currentTabId, this.#previousTabId);
		};

		/**
		 * @param {String} id
		 */
		#rootTabsSelectedHandler = (id) =>	{
			logger.log('onRootTabsSelected id:', id);

			const rootTabChatName = 'chats';
			if (id === rootTabChatName)
			{
				this.#sendAnalyticsChangeTab();
			}
		};

		#subscribeEvents()
		{
			this.#ui.on('onTabSelected', this.#tabSelectedHandler);
			BX.addCustomEvent(EventType.navigation.onRootTabsSelected, this.#rootTabsSelectedHandler);
		}

		#sendAnalyticsChangeTab()
		{
			AnalyticsService.getInstance()
				.sendChangeNavigationTab(this.#currentTabId, this.#activatedTabOptions?.analytics)
			;
		}

		#sendInitialAnalytics()
		{
			NavigationHelper.isMessengerTabActive()
				.then((result) => {
					if (result)
					{
						this.#sendAnalyticsChangeTab();
					}
				})
				.catch((error) => {
					logger.error(`${this.constructor.name}.sendInitialAnalytics error`, error);
				})
			;
		}
	}

	module.exports = { TabSwitcher };
});
