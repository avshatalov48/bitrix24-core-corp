/**
 * @module im/messenger/db/update/version/12
 */
jn.define('im/messenger/db/update/version/12', (require, exports, module) => {
	/**
	 * @param {Updater} updater
	 */
	module.exports = async (updater) => {
		const recentTableName = 'b_im_recent';

		const isRecentTableExist = await updater.isTableExists(recentTableName);
		if (isRecentTableExist)
		{
			const isIdIndexExist = await updater.isIndexExists(recentTableName, 'id');
			if (isIdIndexExist === false)
			{
				await updater.createIndex(recentTableName, 'id', false);
			}

			const isLastActivityDateIndexExist = await updater.isIndexExists(recentTableName, 'lastActivityDate');
			if (isLastActivityDateIndexExist === false)
			{
				await updater.createIndex(recentTableName, 'lastActivityDate', false);
			}
		}
	};
});
