/**
 * @module im/messenger/cache/users
 */
jn.define('im/messenger/cache/users', (require, exports, module) => {

	const { Cache } = require('im/messenger/cache/base');
	const { throttle } = require('utils/function');

	/**
	 * @class RecentCache
	 */
	class UsersCache extends Cache
	{
		constructor()
		{
			super({
				name: 'users',
			});

			this.save = throttle(this.save, 10000, this);
		}
	}

	module.exports = {
		UsersCache: new UsersCache(),
	};
});
