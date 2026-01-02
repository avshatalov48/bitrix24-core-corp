/**
 * @module im/messenger-v2/controller/recent/service/base/base-ui
 */
jn.define('im/messenger-v2/controller/recent/service/base/base-ui', (require, exports, module) => {
	const { createPromiseWithResolvers } = require('im/messenger/lib/utils');
	const { RecentEventType } = require('im/messenger-v2/controller/recent/const');

	const { BaseRecentService } = require('im/messenger-v2/controller/recent/service/base/base');

	/**
	 * @abstract
	 * @template Tprops
	 * @extends {BaseRecentService<Tprops>}
	 */
	class BaseUiRecentService extends BaseRecentService
	{
		#resolveUiReadyPromise = null;

		/**
		 * @param {RecentLocator} recentLocator
		 * @param {string} serviceId
		 * @param {any} props
		 */
		constructor(recentLocator, serviceId, props)
		{
			super(recentLocator, serviceId, props);

			const { promise, resolve } = createPromiseWithResolvers();
			this.uiReadyPromise = promise;
			this.#resolveUiReadyPromise = resolve;

			recentLocator.get('emitter')
				.once(RecentEventType.onInit, this.#initHandler)
			;
		}

		/**
		 * @abstract
		 * @param {BaseList} ui
		 */
		async onUiReady(ui)
		// eslint-disable-next-line no-empty-function
		{}

		#initHandler = () => {
			this.recentLocator.get('ui')
				.then((ui) => {
					return this.onUiReady(ui);
				})
				.then(this.#resolveUiReadyPromise)
				.catch((error) => {
					this.logger.error('onUiReady error', error);
				});
		};

		/**
		 * @abstract
		 */
		onInit()
		{}
	}

	module.exports = { BaseUiRecentService };
});
