/**
 * @module im/messenger/api/dialog-selector/controller
 */
jn.define('im/messenger/api/dialog-selector/controller', (require, exports, module) => {
	const { Theme } = require('im/lib/theme');
	const { RecentProvider, RecentSelector } = require('im/messenger/controller/search/experimental');

	const { DialogSelectorView } = require('im/messenger/api/dialog-selector/view');
	const { LoggerManager } = require('im/messenger/lib/logger');

	const logger = LoggerManager.getInstance().getLogger('dialog-selector');

	/**
	 * @class DialogSelector
	 */
	class DialogSelector extends RecentSelector
	{
		constructor()
		{
			super({});
			this.layout = null;
			/** @type {DialogSelectorView} */
			this.view = null;
			this.isFirstRender = true;
		}

		async show({ title, layout = null })
		{
			return new Promise((resolve, reject) => {
				layout ??= PageManager;

				layout.openWidget('layout', {
					title,
					useLargeTitleMode: true,
					modal: true,
					backgroundColor: Theme.colors.bgNavigation,
					backdrop: {
						mediumPositionPercent: 85,
						horizontalSwipeAllowed: false,
						onlyMediumPosition: true,
					},
				}).then((layoutWidget) => {
					this.layout = layoutWidget;
					this.view = new DialogSelectorView({
						onChangeText: (text) => {
							this.onUserTypeText({ text });
						},
						onItemSelected: (dialogParams) => {
							this.close(() => {
								resolve({
									dialogId: dialogParams.dialogId,
									name: dialogParams.dialogTitleParams.name,
								});
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
				})
					.catch((error) => {
						reject(error);
					});
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

		close(callback)
		{
			super.close();
			this.layout.close(callback);
		}

		subscribeEvents() {}

		unsubscribeEvents() {}
	}

	module.exports = { DialogSelector };
});
