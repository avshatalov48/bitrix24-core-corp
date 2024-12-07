/**
 * @module im/messenger/db/update/version/8
 */
jn.define('im/messenger/db/update/version/8', (require, exports, module) => {
	/**
	 * @param {Updater} updater
	 */
	module.exports = async (updater) => {
		const isDialogTableExist = await updater.isTableExists('b_im_dialog');
		if (isDialogTableExist === false)
		{
			return;
		}

		const isRoleFieldExist = await updater
			.isColumnExists('b_im_dialog', 'role')
		;

		if (isRoleFieldExist === false)
		{
			updater.executeSql({
				query: "ALTER TABLE b_im_dialog ADD COLUMN role TEXT",
			});
		}
	};
});