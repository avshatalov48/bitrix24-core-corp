/**
 * @module im/messenger/db/update/version/13
 */
jn.define('im/messenger/db/update/version/13', (require, exports, module) => {
	/**
	 * @param {Updater} updater
	 */
	module.exports = async (updater) => {
		const isUserTableExist = await updater.isTableExists('b_im_user');
		if (isUserTableExist === true)
		{
			const isTypeFieldExist = await updater
				.isColumnExists('b_im_user', 'type')
			;

			if (isTypeFieldExist === false)
			{
				updater.executeSql({
					query: 'ALTER TABLE b_im_user ADD COLUMN type TEXT',
				});
			}
		}
	};
});
