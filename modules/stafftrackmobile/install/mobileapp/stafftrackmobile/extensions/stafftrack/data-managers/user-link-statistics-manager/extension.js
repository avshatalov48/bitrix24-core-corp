/**
 * @module stafftrack/data-managers/user-link-statistics-manager
 */
jn.define('stafftrack/data-managers/user-link-statistics-manager', (require, exports, module) => {
	const { UserLinkStatisticsAjax } = require('stafftrack/ajax');

	class UserLinkStatisticsManager
	{
		constructor()
		{
			this.users = {};
		}

		async get(userId, hash)
		{
			this.users[userId] ??= await this.load(userId, hash);

			return this.users[userId];
		}

		async load(userId, hash)
		{
			this.usersPromises ??= {};
			this.usersPromises[userId] ??= UserLinkStatisticsAjax.get(userId, hash);

			const { data } = await this.usersPromises[userId];
			if (data?.user)
			{
				return data.user;
			}

			delete this.usersPromises[userId];

			return null;
		}
	}

	module.exports = { UserLinkStatisticsManager: new UserLinkStatisticsManager() };
});
