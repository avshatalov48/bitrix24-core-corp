/**
 * @module im/messenger/db/update/version/17
 */
jn.define('im/messenger/db/update/version/17', (require, exports, module) => {
	/**
	 * @param {Updater} updater
	 */
	module.exports = async (updater) => {
		const isDialogTableExist = await updater.isTableExists('b_im_dialog');
		if (isDialogTableExist === true)
		{
			const isContainsCollaberColumnExist = await updater
				.isColumnExists('b_im_dialog', 'containsCollaber')
			;
			if (isContainsCollaberColumnExist === false)
			{
				await updater.executeSql({
					query: "ALTER TABLE b_im_dialog ADD COLUMN containsCollaber TEXT DEFAULT '0'",
				});
			}
		}
	};
});
