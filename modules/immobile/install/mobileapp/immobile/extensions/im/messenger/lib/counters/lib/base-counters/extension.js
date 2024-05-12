/**
 * @module im/messenger/lib/counters/lib/base-counters
 */
jn.define('im/messenger/lib/counters/lib/base-counters', (require, exports, module) => {
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { restManager } = require('im/messenger/lib/rest-manager');
	const { RestMethod } = require('im/messenger/const');
	const { Logger } = require('im/messenger/lib/logger');

	/**
	 * @class BaseCounters
	 */
	class BaseCounters
	{
		constructor(options = {})
		{
			this.store = serviceLocator.get('core').getStore();
			this.logger = options.logger || Logger;

			this.updateTimeout = null;
			this.updateInterval = 300;

			this.bindMethods();
			this.initCounters();
		}

		bindMethods()
		{
			this.handleCountersGet = this.handleCountersGet.bind(this);
		}

		initCounters()
		{}

		initRequests()
		{
			restManager.on(RestMethod.imV2CountersGet, this.getRestOptions(), this.handleCountersGet);
		}

		getRestOptions()
		{
			return { JSON: 'Y' };
		}

		handleCountersGet()
		{}

		updateDelayed()
		{
			this.logger.log(`${this.getClassName()}.updateDelayed`);

			if (!this.updateTimeout)
			{
				this.updateTimeout = setTimeout(() => this.update(), this.updateInterval);
			}
		}

		update()
		{}

		/**
		 * @param {RecentModelState} recentItem
		 * @param {DialoguesModelState} dialogItem
		 * @return {number}
		 */
		calculateItemCounter(recentItem = {}, dialogItem = {})
		{
			let counter = 0;
			if (dialogItem.counter && dialogItem.counter > 0)
			{
				counter = dialogItem.counter;
			}
			else if (recentItem.unread)
			{
				counter = 1;
			}

			return counter;
		}

		clearUpdateTimeout()
		{
			clearTimeout(this.updateTimeout);
			this.updateTimeout = null;
		}

		/**
		 * @desc get class name for logger
		 * @return {string}
		 */
		getClassName()
		{
			return this.constructor.name;
		}
	}

	module.exports = { BaseCounters };
});
