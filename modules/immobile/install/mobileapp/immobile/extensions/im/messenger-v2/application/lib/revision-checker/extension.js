/**
 * @module im/messenger-v2/application/lib/revision-checker
 */
jn.define('im/messenger-v2/application/lib/revision-checker', (require, exports, module) => {
	const { Type } = require('type');

	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { getLoggerWithContext } = require('im/messenger/lib/logger');

	/**
	 * @class RevisionChecker
	 */
	class RevisionChecker
	{
		#clientRevision;

		/**
		 * @param {number} clientRevision
		 */
		constructor(clientRevision)
		{
			this.logger = getLoggerWithContext('messenger-v2--revision-checker', this);
			this.#clientRevision = clientRevision;
			this.#bindMethods();
		}

		/**
		 * @return {MessengerInitService}
		 */
		get #messengerInitService()
		{
			return serviceLocator.get('messenger-init-service');
		}

		#bindMethods()
		{
			this.reloadAppIfNeededHandler = this.reloadAppIfNeeded.bind(this);
		}

		subscribeInitMessengerEvent()
		{
			this.#messengerInitService.onInit(this.reloadAppIfNeededHandler);
		}

		/**
		 * @protected
		 * @param {immobileTabChatLoadResult} data
		 */
		reloadAppIfNeeded(data)
		{
			const serverRevision = data?.mobileRevision;
			if (!Type.isNumber(serverRevision) || this.#clientRevision >= serverRevision)
			{
				this.logger.log(`✅ ${this.constructor.name}: clientRevision=${this.#clientRevision}, serverRevision=${serverRevision}`);

				return false;
			}

			this.logger.warn(`🔄 ${this.constructor.name} reload because: clientRevision=${this.#clientRevision}, serverRevision=${serverRevision}`);

			this.reloadApp();

			return true;
		}

		/**
		 * @protected
		 */
		reloadApp()
		{
			Application.relogin();
		}
	}

	module.exports = { RevisionChecker };
});
