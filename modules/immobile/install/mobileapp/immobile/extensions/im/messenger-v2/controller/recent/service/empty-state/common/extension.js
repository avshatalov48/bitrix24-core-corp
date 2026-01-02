/**
 * @module im/messenger-v2/controller/recent/service/empty-state/common
 */
jn.define('im/messenger-v2/controller/recent/service/empty-state/common', (require, exports, module) => {
	const { Type } = require('type');
	const { isEqual } = require('utils/object');
	const { RecentEventType } = require('im/messenger-v2/controller/recent/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { BaseUiRecentService } = require('im/messenger-v2/controller/recent/service/base');

	/**
	 * @implements {IEmptyStateService}
	 * @class CommonEmptyStateService
	 * @extends {BaseUiRecentService<CommonEmptyStateServiceProps>}
	 */
	class CommonEmptyStateService extends BaseUiRecentService
	{
		onInit()
		{
			this.logger.log('onInit');

			this.WelcomeScreenClass = require(this.props.welcomeScreenExtension);
			this.recentCollectionSize = null;
			this.subscribeEvents();
		}

		/**
		 * @param {BaseList} ui
		 */
		async onUiReady(ui)
		{
			this.logger.log('onUiReady');

			this.ui = ui;
			this.renderedWelcomeScreen = null;
		}

		get isWelcomeScreenShown()
		{
			return !Type.isNull(this.renderedWelcomeScreen);
		}

		async show()
		{
			this.logger.log('show');
			await this.uiReadyPromise;
			this.renderWelcomeScreenIfNeeded();
		}

		async hide()
		{
			this.logger.log('hide');
			await this.uiReadyPromise;
			if (this.isWelcomeScreenShown === false)
			{
				return;
			}

			this.ui.welcomeScreen.hide();
			this.renderedWelcomeScreen = null;
			this.logger.log('hide complete');
		}

		/**
		 * @private
		 */
		subscribeEvents()
		{
			this.recentLocator.get('emitter')
				.on(
					RecentEventType.render.updateUIByRecentCollectionSizeIfNeeded,
					this.updateUIByRecentCollectionSizeIfNeededHandler,
				)
			;
		}

		/**
		 * @private
		 * @return {MessengerCoreStore}
		 */
		get store()
		{
			return serviceLocator.get('core').getStore();
		}

		/**
		 * @private
		 * @return {Promise<void>}
		 */
		updateUIByRecentCollectionSizeIfNeededHandler = async () => {
			this.logger.log('updateUIByRecentCollectionSizeIfNeededHandler');
			const tabId = this.recentLocator.get('id');
			const recentCollectionSize = this.store.getters['recentModel/getCollectionSizeByTabId'](tabId);
			if (recentCollectionSize === this.recentCollectionSize)
			{
				this.logger.log('updateUIByRecentCollectionSizeIfNeededHandler skipped');

				return;
			}

			if (recentCollectionSize > 0)
			{
				await this.hide();
			}
			else
			{
				await this.show();
			}

			this.recentCollectionSize = recentCollectionSize;
			this.logger.log('updateUIByRecentCollectionSizeIfNeededHandler complete');
		};

		/**
		 * @private
		 */
		renderWelcomeScreenIfNeeded()
		{
			const welcomeScreen = new this.WelcomeScreenClass();
			if (isEqual(welcomeScreen, this.renderedWelcomeScreen))
			{
				this.logger.log('renderWelcomeScreenIfNeeded skipped');

				return;
			}

			this.ui.welcomeScreen.show(welcomeScreen.toChatRecentWidgetItem());

			this.renderedWelcomeScreen = welcomeScreen;
			this.logger.log('renderWelcomeScreenIfNeeded complete');
		}
	}

	module.exports = CommonEmptyStateService;
});
