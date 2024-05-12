/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/controller/recent/copilot/recent
 */
jn.define('im/messenger/controller/recent/copilot/recent', (require, exports, module) => {
	const { clone } = require('utils/object');
	const { CopilotRecentCache } = require('im/messenger/cache');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { BaseRecent } = require('im/messenger/controller/recent/lib');
	const { restManager } = require('im/messenger/lib/rest-manager');
	const { RestMethod, EventType, ComponentCode } = require('im/messenger/const');
	const { LoggerManager } = require('im/messenger/lib/logger');

	const logger = LoggerManager.getInstance().getLogger('recent--copilot-recent');

	/**
	 * @class CopilotRecent
	 */
	class CopilotRecent extends BaseRecent
	{
		constructor(options = {})
		{
			super({ ...options, logger });
		}

		bindMethods()
		{
			this.recentAddHandler = this.recentAddHandler.bind(this);
			this.recentUpdateHandler = this.recentUpdateHandler.bind(this);
			this.recentDeleteHandler = this.recentDeleteHandler.bind(this);
			this.dialogUpdateHandler = this.dialogUpdateHandler.bind(this);

			this.firstPageHandler = this.firstPageHandler.bind(this);
			this.stopRefreshing = this.stopRefreshing.bind(this);
			this.renderInstant = this.renderInstant.bind(this);
			this.loadPage = this.loadPage.bind(this);
		}

		fillStoreFromCache()
		{
			this.recentCache = new CopilotRecentCache({
				storeManager: this.storeManager,
			});
			const cache = this.recentCache.get();

			this.logger.info(`${this.getClassName()}.fillStoreFromCache cache:`, cache);

			return this.fillStore(cache);
		}

		initRequests()
		{
			restManager.on(
				RestMethod.imRecentList,
				this.getRestRecentListOptions(),
				this.firstPageHandler.bind(this),
			);

			this.countersInitRequest();
		}

		subscribeViewEvents()
		{
			this.view
				.on(EventType.recent.itemSelected, this.onItemSelected.bind(this))
				.on(EventType.recent.loadNextPage, this.onLoadNextPage.bind(this))
				.on(EventType.recent.itemAction, this.onItemAction.bind(this))
				.on(EventType.recent.createChat, this.onCreateChat.bind(this))
				.on(EventType.recent.refresh, this.onRefresh.bind(this))
			;
		}

		subscribeStoreEvents()
		{
			this.storeManager
				.on('recentModel/add', this.recentAddHandler)
				.on('recentModel/update', this.recentUpdateHandler)
				.on('recentModel/delete', this.recentDeleteHandler)
				.on('dialoguesModel/add', this.dialogUpdateHandler)
				.on('dialoguesModel/update', this.dialogUpdateHandler)
			;
		}

		subscribeMessengerEvents()
		{
			BX.addCustomEvent(EventType.messenger.afterRefreshSuccess, this.stopRefreshing);
			BX.addCustomEvent(EventType.messenger.renderRecent, this.renderInstant);
		}

		/**
		 * @return {object}
		 */
		getRestRecentListOptions()
		{
			return { ONLY_COPILOT: 'Y', SKIP_OPENLINES: 'Y' };
		}

		/**
		 * @return {object}
		 */
		getRestListOptions()
		{
			return { skipOpenlines: true, onlyCopilot: true };
		}

		/* region Events */

		onItemSelected(recentItem)
		{
			if (recentItem.params.disableTap)
			{
				return;
			}

			this.openDialog(recentItem.id, ComponentCode.imCopilotMessenger);
		}

		onLoadNextPage()
		{
			const canLoadNextPage = !this.pageNavigation.isPageLoading && this.pageNavigation.hasNextPage;
			if (!canLoadNextPage)
			{
				return;
			}

			this.loadNextPage();
		}

		onItemAction(event)
		{
			const action = event.action.identifier;
			const itemId = event.item.params.id;

			this.itemAction.do(action, itemId);
		}

		onCreateChat()
		{
			MessengerEmitter.emit(EventType.messenger.createChat, {}, ComponentCode.imCopilotMessenger);
		}

		onRefresh()
		{
			MessengerEmitter.emit(EventType.messenger.refresh, true, ComponentCode.imCopilotMessenger);
		}

		/* endregion Events */
		recentAddHandler(mutation)
		{
			const recentList = [];
			const recentItemList = clone(mutation.payload.data.recentItemList);

			recentItemList.forEach((item) => recentList.push(item.fields));

			this.addItems(recentList);
		}

		recentUpdateHandler(mutation)
		{
			const recentList = [];

			mutation.payload.data.recentItemList.forEach((item) => {
				recentList.push(clone(this.store.getters['recentModel/getCollection']()[item.index]));
			});

			this.updateItems(recentList);
		}

		recentDeleteHandler(mutation)
		{
			this.renderer.removeFromQueue(mutation.payload.data.id);

			this.view.removeItem({ id: mutation.payload.data.id });
			if (!this.pageNavigation.hasNextPage && this.view.isLoaderShown)
			{
				this.view.hideLoader();
			}

			this.checkEmpty();
		}

		dialogUpdateHandler(mutation)
		{
			const dialogId = mutation.payload.data.dialogId;
			const recentItem = clone(this.store.getters['recentModel/getById'](dialogId));
			if (recentItem)
			{
				this.updateItems([recentItem]);
			}
		}

		showWelcomeScreen()
		{
			this.view.showWelcomeScreen();
		}

		/**
		 * @override
		 * @param {Array} recentItems
		 * @return {Promise}
		 */
		saveShareDialogCache(recentItems)
		{
			return Promise.resolve(true);
		}
	}

	module.exports = { CopilotRecent };
});
