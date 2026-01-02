/**
 * @module im/messenger-v2/controller/recent/service/base/base
 */
jn.define('im/messenger-v2/controller/recent/service/base/base', (require, exports, module) => {
	const { getLoggerWithContext } = require('im/messenger/lib/logger');
	const { RecentEventType } = require('im/messenger-v2/controller/recent/const');

	/**
	 * @template Tprops
	 * @abstract
	 * @class BaseRecentService
	 */
	class BaseRecentService
	{
		/**
		 * @param {RecentLocator} recentLocator
		 * @param {string} serviceId
		 * @param {any} props
		 */
		constructor(recentLocator, serviceId, props)
		{
			/**
			 * @type {RecentLocator}
			 */
			this.recentLocator = recentLocator;
			/** @type {Tprops} */
			this.props = props;
			this.logger = getLoggerWithContext(`recent-service--${serviceId}`, `${recentLocator.get('id')} ${this.constructor.name}`);

			recentLocator.get('emitter')
				.once(RecentEventType.onInit, () => {
					this.logger.info('start init');
					this.onInit();
				})
			;
		}

		/**
		 * @abstract
		 */
		onInit()
		{}
	}

	module.exports = { BaseRecentService };
});
