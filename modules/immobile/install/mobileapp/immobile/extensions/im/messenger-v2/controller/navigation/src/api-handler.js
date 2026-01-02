/**
 * @module im/messenger-v2/controller/navigation/api-handler
 */
jn.define('im/messenger-v2/controller/navigation/api-handler', (require, exports, module) => {
	const {
		EventType,
	} = require('im/messenger/const');
	const { waitViewLoaded } = require('im/messenger-v2/lib/wait-view-loaded');
	const { NavigationController } = require('im/messenger-v2/controller/navigation/controller');

	const { NavigationHelper } = require('im/messenger-v2/controller/navigation/helper');

	/**
	 * @class NavigationApiHandler
	 */
	class NavigationApiHandler
	{
		static #instance = null;
		/**
		 * @return {NavigationApiHandler}
		 */
		static getInstance()
		{
			if (!this.#instance)
			{
				this.#instance = new this();
			}

			return this.#instance;
		}

		constructor()
		{
			this.subscribeEvents();
		}

		subscribeEvents()
		{
			BX.addCustomEvent(EventType.navigation.changeTab, this.#changeTabHandler);
			BX.addCustomEvent(EventType.navigation.closeAll, this.#closeAllHandler);
		}

		/**
		 * @param {string} tabId
		 * @param {TabOptions} options
		 * @return {Promise<void>}
		 */
		#changeTabHandler = async (tabId, options = {}) => {
			if (!NavigationHelper.isTabIdCorrect(tabId))
			{
				return this.#sendIncorrectTabIdError(tabId);
			}

			if (!NavigationHelper.isTabAvailable(tabId))
			{
				return this.#sendTabUnavailableError(tabId);
			}

			await NavigationHelper.makeMessengerTabActive();

			await waitViewLoaded();

			const result = await NavigationController.getInstance()
				.setActiveTab(tabId, options)
			;

			if (result.isSuccess())
			{
				return BX.postComponentEvent(EventType.navigation.changeTabResult, [{
					tabId,
				}]);
			}

			if (result.fromIncorrectTabId())
			{
				return this.#sendIncorrectTabIdError(tabId);
			}

			if (result.fromTabIsNotAvailable())
			{
				return this.#sendIncorrectTabIdError(tabId);
			}

			return BX.postComponentEvent(EventType.navigation.changeTabResult, [{
				tabId,
				errorText: `${result.getError()}: ${tabId}`,
			}]);
		};

		#closeAllHandler = async () => {
			await waitViewLoaded();

			NavigationController.getInstance()
				.closeAllWidgets()
				.then(() => {
					BX.postComponentEvent(EventType.navigation.closeAllComplete, [{ isSuccess: true }]);
				})
				.catch((error) => {
					BX.postComponentEvent(EventType.navigation.closeAllComplete, [{ isSuccess: false }]);
				})
			;
		};

		#sendIncorrectTabIdError(tabId)
		{
			BX.postComponentEvent(EventType.navigation.changeTabResult, [{
				tabId,
				errorText: `im.messenger: Error changing tab, tab ${tabId} does not exist.`,
			}]);
		}

		#sendTabUnavailableError(tabId)
		{
			BX.postComponentEvent(EventType.navigation.changeTabResult, [{
				tabId,
				errorText: `im.navigation: Error changing tab, tab ${tabId} is disabled.`,
			}]);
		}
	}

	module.exports = { NavigationApiHandler };
});
