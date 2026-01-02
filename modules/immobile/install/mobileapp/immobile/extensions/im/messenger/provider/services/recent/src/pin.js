/**
 * @module im/messenger/provider/services/recent/pin
 */
jn.define('im/messenger/provider/services/recent/pin', (require, exports, module) => {
	const { Alert, ButtonType } = require('alert');
	const { Loc } = require('im/messenger/loc');
	const { ErrorType } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Runtime } = require('runtime');
	const { Logger } = require('im/messenger/lib/logger');

	const { RecentRest } = require('im/messenger/provider/rest');

	/**
	 * @class PinService
	 */
	class PinService
	{
		/**
		 * @constructor
		 * @param {object} store
		 */
		constructor()
		{
			this.store = serviceLocator.get('core').getStore();
			this.sendPinRequestDebounced = Runtime.debounce(this.sendPinRequest, 500);
			this.sendUnpinRequestDebounced = Runtime.debounce(this.sendUnpinRequest, 500);
		}

		/**
		 * @param {DialogId} dialogId
		 */
		pinChat(dialogId)
		{
			Logger.log(`${this.constructor.name}.pinChat dialogId:`, dialogId);
			this.#updateRecentModelPin(dialogId, true);

			this.sendPinRequestDebounced(dialogId);
		}

		/**
		 * @param {DialogId} dialogId
		 */
		unpinChat(dialogId)
		{
			Logger.log(`${this.constructor.name}.unpinChat dialogId:`, dialogId);
			this.#updateRecentModelPin(dialogId, false);

			this.sendUnpinRequestDebounced(dialogId);
		}

		/**
		 * @desc Send request with call method
		 * @param {DialogId} dialogId
		 */
		async sendPinRequest(dialogId)
		{
			RecentRest.pinChat(dialogId)
				.catch((error) => {
					Logger.error(`${this.constructor.name}.sendPinRequest error:`, error);

					const errorCode = error?.[0]?.code;
					if (errorCode === ErrorType.recent.maxPin)
					{
						Alert.confirm(
							null,
							Loc.getMessage('IMMOBILE_MESSENGER_PROVIDER_SERVICES_RECENT_ERROR_MAX_PINNED'),
							[
								{
									type: ButtonType.DEFAULT,
								},
							],
						);
					}

					this.#updateRecentModelPin(dialogId, false);
				})
			;
		}

		/**
		 * @desc Send request with call method
		 * @param {string} dialogId
		 */
		async sendUnpinRequest(dialogId)
		{
			RecentRest.unpinChat(dialogId)
				.catch((error) => {
					Logger.error(`${this.constructor.name}.sendUnpinRequest error:`, error);

					this.#updateRecentModelPin(dialogId, true);
				})
			;
		}

		/**
		 * @param {DialogId} dialogId
		 * @param {boolean} pinned
		 */
		#updateRecentModelPin(dialogId, pinned)
		{
			this.store.dispatch('recentModel/set', [{
				id: dialogId,
				pinned,
			}]);
		}
	}

	module.exports = {
		PinService,
	};
});
