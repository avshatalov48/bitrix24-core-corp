/**
 * @module im/messenger/controller/forward-selector/selector
 */

jn.define('im/messenger/controller/forward-selector/selector', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { EventType } = require('im/messenger/const');
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

			this.initProvider();
		}

		open({ messageId, fromDialogId, locator })
		{
			PageManager.openWidget('layout', {
				title: Loc.getMessage('IMMOBILE_MESSENGER_FORWARD_SELECTOR_TITLE'),
				useLargeTitleMode: true,
				modal: true,
				backgroundColor: AppTheme.colors.bgNavigation,
				backdrop: {
					mediumPositionPercent: 85,
					horizontalSwipeAllowed: false,
					onlyMediumPosition: true,
				},
			}).then((layoutWidget) => {
				this.layout = layoutWidget;
				this.view = new ForwardSelectorView({
					onChangeText: (text) => {
						this.onUserTypeText({ text });
					},
					onItemSelected: (dialogParams) => {
						this.forwardMessage({
							messageId,
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
				logger.log(`${this.constructor.name} show component`);
			});
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
		 * @param {number} messageId
		 * @param {MessengerItemOnClickParams} dialogParams
		 * @param {DialogId} fromDialogId
		 * @param {DialogLocator} locator
		 */
		forwardMessage({ messageId, dialogParams, fromDialogId, locator })
		{
			logger.log(`${this.constructor.name} forwardMessage`, messageId, dialogParams, fromDialogId);
			const userModel = serviceLocator.get('core').getStore().getters['usersModel/getById'](dialogParams.dialogId);
			if (userModel?.bot)
			{
				Notification.showComingSoon(); // TODO delete when bots are available

				return;
			}

			if (dialogParams.dialogId.toString() === fromDialogId.toString())
			{
				locator.get('reply-manager').startForwardingMessage(messageId);
				this.close();

				return;
			}

			const openDialogParams = {
				...dialogParams,
				forwardMessageId: messageId,
			};

			MessengerEmitter.emit(EventType.messenger.openDialog, openDialogParams, 'im.messenger');
			this.close();
		}

		close()
		{
			super.close();
			this.layout.close();
		}

		subscribeEvents() {}

		unsubscribeEvents() {}
	}

	module.exports = { ForwardSelector };
});
