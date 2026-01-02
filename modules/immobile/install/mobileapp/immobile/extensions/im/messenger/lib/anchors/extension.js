/**
 * @module im/messenger/lib/anchors
 */
jn.define('im/messenger/lib/anchors', (require, exports, module) => {
	const { Type } = require('type');
	const { Logger } = require('im/messenger/lib/logger');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	/**
	 * @class Anchors
	 */
	class Anchors
	{
		constructor()
		{
			this.serviceLocator = serviceLocator;
			/**
			 * @type {CoreApplication}
			 */
			this.core = this.serviceLocator.get('core');
			/**
			 * @type {MessengerCoreStore}
			 */
			this.store = this.core.getStore();
			/**
			 * @type {MessengerInitService}
			 */
			this.messengerInitService = serviceLocator.get('messenger-init-service');

			this.subscribeInitMessengerEvent();
		}

		subscribeInitMessengerEvent()
		{
			this.messengerInitService.onInit(this.updateAnchors);
		}

		/**
		 * @private
		 * @param {immobileTabChatLoadResult} data
		 * @return boolean
		 */
		updateAnchors = (data) => {
			const anchors = data.anchors;
			Logger.log('Anchors.updateAnchors', anchors);

			if (!Type.isArray(anchors))
			{
				Logger.log('Anchors.updateAnchors not valid anchors', anchors);

				return false;
			}

			this.store.dispatch('anchorModel/setState', anchors);

			return true;
		};
	}

	module.exports = {
		Anchors,
	};
});
