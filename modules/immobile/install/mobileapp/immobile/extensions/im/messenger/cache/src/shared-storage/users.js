/* eslint-disable bitrix-rules/no-pseudo-private */
/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-bx-message */

/**
 * @module im/messenger/cache/users
 */
jn.define('im/messenger/cache/users', (require, exports, module) => {

	const { Cache } = jn.require('im/messenger/cache/base');
	const { throttle } = jn.require('utils/function');

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
