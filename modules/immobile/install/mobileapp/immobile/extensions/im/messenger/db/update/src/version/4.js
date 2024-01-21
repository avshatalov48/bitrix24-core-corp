/**
 * @module im/messenger/db/update/version/4
 */
jn.define('im/messenger/db/update/version/4', (require, exports, module) => {
	/**
	 * @param {Updater} updater
	 */
	module.exports = async (updater) => {
		const isUserTableExist = await updater.isTableExists('b_im_user');
		if (isUserTableExist === true)
		{
			const isBotDataFieldExist = await updater
				.isColumnExists('b_im_user', 'botData')
			;

			if (isBotDataFieldExist === false)
			{
				updater.executeSql({
					query: 'ALTER TABLE b_im_user ADD COLUMN botData TEXT',
				});
			}
		}
	};
});
