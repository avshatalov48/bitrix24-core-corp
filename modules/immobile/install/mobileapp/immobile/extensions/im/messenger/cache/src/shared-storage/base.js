/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/cache/base
 */
jn.define('im/messenger/cache/base', (require, exports, module) => {

	const { Type } = require('type');

	class Cache
	{
		constructor(options)
		{
			if (!options.name)
			{
				throw new Error('Cache: options.storageId is required');
			}

			if (!Type.isString(options.name))
			{
				throw new Error('Cache: options.storageId must be a string value');
			}

			const namespace = 'im/messenger/cache/v1.2/';
			const name = options.name;

			this.storageId = namespace + name;

			this.storage = Application.storageById(this.storageId);
		}

		get()
		{
			const state = this.storage.getObject('state').state;
			if (state && Type.isObject(state) && Object.keys(state).length === 0)
			{
				return false;
			}

			return state;
		}

		save(state)
		{
			return new Promise((resolve, reject) =>
			{
				this.storage.setObject('state', { state });

				resolve();
			});
		}

		clear()
		{
			return new Promise((resolve, reject) =>
			{
				this.storage.clear();

				resolve();
			});
		}
	}

	module.exports = {
		Cache,
	};
});
