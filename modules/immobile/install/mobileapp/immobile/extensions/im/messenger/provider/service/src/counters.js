/**
 * @module im/messenger/provider/service/counters
 */
jn.define('im/messenger/provider/service/counters', (require, exports, module) => {
	const { Type } = require('type');

	const { RestMethod } = require('im/messenger/const');
	const { Logger } = require('im/messenger/lib/logger');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	/**
	 * @class CountersService
	 */
	class CountersService
	{
		/**
		 * @return {CountersService}
		 */
		static getInstance()
		{
			if (!this.instance)
			{
				this.instance = new this();
			}

			return this.instance;
		}

		constructor()
		{
			this.store = serviceLocator.get('core').getStore();
		}

		async load()
		{
			return Promise.resolve();

			// const counters = await this.get();
			// this.updateModels(counters);
		}

		async get()
		{
			let counters = {};
			try
			{
				const response = await BX.rest.callMethod(RestMethod.imCountersGet, { JSON: 'Y' });
				counters = response.data();
			}
			catch (error)
			{
				Logger.error(error);
			}

			return counters;
		}

		updateModels(counters)
		{
			const {
				chat,
				chatMuted,
				chatUnread,
			} = counters;

			Logger.log('CountersService.updateModels: ', counters);
		}
	}

	module.exports = {
		CountersService,
	};
});
