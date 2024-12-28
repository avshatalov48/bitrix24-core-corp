/**
 * @module im/messenger/controller/forward-selector/selector
 */

jn.define('im/messenger/controller/forward-selector/selector', (require, exports, module) => {
	const { Loc } = require('loc');

	const { Theme } = require('im/lib/theme');
	const { Feature } = require('im/messenger/lib/feature');
	const { EventType, WidgetTitleParamsType } = require('im/messenger/const');
	const { RecentProvider, RecentSelector } = require('im/messenger/controller/search/experimental');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { ForwardSelectorView } = require('im/messenger/controller/forward-selector/view');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Notification } = require('im/messenger/lib/ui/notification');
	const { LoggerManager } = require('im/messenger/lib/logger');

	const logger = LoggerManager.getInstance().getLogger('forward-selector');

	/**
	 * @class ForwardSelector
	 */
	class ForwardSelector extends RecentSelector
	{
		constructor()
		{
			super({}); // TODO hack ;)
			/**
			 *
			 * @type {RecentProvider}
			 */
			this.provider = null;
			this.layout = null;
			/** @type {ForwardSelectorView} */
			this.view = null;
			this.isFirstRender = true;
			this.fromDialogId = null;

			this.initProvider();
		}

		async open({ messageIds, fromDialogId, locator, onItemSelectedCallBack = () => {} })
		{
			this.fromDialogId = fromDialogId;
			this.bindMethods();
			this.subscribeExternalEvents();

			const layoutWidget = await PageManager.openWidget('layout', {
				titleParams: {
					text: Loc.getMessage('IMMOBILE_MESSENGER_FORWARD_SELECTOR_TITLE'),
					type: WidgetTitleParamsType.entity,
				},
				useLargeTitleMode: true,
				modal: true,
				backgroundColor: Theme.colors.bgNavigation,
				backdrop: {
					mediumPositionPercent: 85,
					horizontalSwipeAllowed: false,
					onlyMediumPosition: true,
				},
			});

			this.layout = layoutWidget;
			this.view = new ForwardSelectorView({
				onChangeText: (text) => {
					this.onUserTypeText({ text });
				},
				onItemSelected: (dialogParams) => {
					onItemSelectedCallBack();
					this.forwardMessage({
						messageIds,
						dialogParams,
						fromDialogId,
						locator,
					});
				},
				onMount: () => {
					if (this.isFirstRender)
					{
						this.provider.loadLatestSearch();
						this.isFirstRender = false;
					}
				},
				openingLoaderTitle: this.getLoadingItem().title,
			});
			layoutWidget.showComponent(this.view);

			layoutWidget.on(EventType.view.close, () => {
				this.unsubscribeExternalEvents();
			});

			logger.log(`${this.constructor.name} show component`);
		}

		initProvider()
		{
			this.provider = new RecentProvider({
				loadLatestSearchComplete: (itemIdList) => {
					logger.log(`${this.constructor.name} loadLatestSearchComplete`, itemIdList);
					this.recentItems = itemIdList;
					this.view.setItems(itemIdList, false);
				},
				loadSearchProcessed: (itemIdList, withLoader) => {
					logger.log(`${this.constructor.name} loadSearchProcessed`, itemIdList, withLoader);
					this.view.setItems(itemIdList, withLoader);
				},
				loadSearchComplete: (searchIds, query) => {
					if (this.processedQuery !== query)
					{
						return;
					}
					logger.log(`${this.constructor.name} loadSearchComplete`, searchIds, query);

					this.view.setItems(searchIds, false);
				},
			});
		}

		drawRecent(recentIds, withLoader = this.isRecentLoading)
		{
			this.view.setItems(recentIds, withLoader);
		}

		/**
		 * @param {Array<number>} messageIds
		 * @param {MessengerItemOnClickParams} dialogParams
		 * @param {DialogId} fromDialogId
		 * @param {DialogLocator} locator
		 */
		forwardMessage({ messageIds, dialogParams, fromDialogId, locator })
		{
			logger.log(`${this.constructor.name} forwardMessage`, messageIds, dialogParams, fromDialogId);
			const userModel = serviceLocator.get('core').getStore().getters['usersModel/getById'](dialogParams.dialogId);
			if (userModel?.bot && !Feature.isChatDialogWidgetSupportsBots)
			{
				Notification.showComingSoon(); // TODO delete when bots are available

				return;
			}

			if (dialogParams.dialogId.toString() === fromDialogId.toString())
			{
				locator.get('reply-manager').startForwardingMessages(messageIds);
				this.close();

				return;
			}

			const openDialogParams = {
				...dialogParams,
				forwardMessageIds: messageIds,
			};

			MessengerEmitter.emit(EventType.messenger.openDialog, openDialogParams, 'im.messenger');
			this.close();
		}

		close()
		{
			super.close();
			this.layout.close();
		}

		bindMethods()
		{
			this.deleteDialogHandler = this.deleteDialogHandler.bind(this);
		}

		subscribeExternalEvents()
		{
			BX.addCustomEvent(EventType.dialog.external.delete, this.deleteDialogHandler);
		}

		unsubscribeExternalEvents()
		{
			BX.removeCustomEvent(EventType.dialog.external.delete, this.deleteDialogHandler);
		}

		deleteDialogHandler({ dialogId })
		{
			if (String(this.fromDialogId) !== String(dialogId))
			{
				return;
			}

			this.close();
		}

		subscribeEvents() {}

		unsubscribeEvents() {}
	}

	module.exports = { ForwardSelector };
});
