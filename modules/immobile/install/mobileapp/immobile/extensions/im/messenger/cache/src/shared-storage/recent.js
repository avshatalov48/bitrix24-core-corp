/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/cache/recent
 */
jn.define('im/messenger/cache/recent', (require, exports, module) => {

	const { Cache } = jn.require('im/messenger/cache/base');

	/**
	 * @class RecentCache
	 */
	class RecentCache extends Cache
	{
		constructor()
		{
			super({
				name: 'recent',
			});
		}

		save(state)
		{
			const firstPageState = ChatUtils.objectClone(state);

			firstPageState.collection =
				firstPageState.collection
					.sort(this.sortListByMessageDate)
					.filter((recentItem, index) => index < 50)
			;

			return super.save(firstPageState);
		}

		sortListByMessageDate(a, b)
		{
			if (!a.pinned && b.pinned)
			{
				return 1;
			}

			if (a.pinned && !b.pinned)
			{
				return -1;
			}

			if (a.message && b.message)
			{
				const timestampA = new Date(a.message.date).getTime();
				const timestampB = new Date(b.message.date).getTime();

				return timestampB - timestampA;
			}
		}
	}

	module.exports = {
		RecentCache: new RecentCache(),
	};
});
