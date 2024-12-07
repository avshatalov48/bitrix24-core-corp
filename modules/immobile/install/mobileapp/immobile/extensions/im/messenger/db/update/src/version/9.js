/**
 * @module im/messenger/db/update/version/9
 */
jn.define('im/messenger/db/update/version/9', (require, exports, module) => {

	/**
	 * @param {Updater} updater
	 */
	module.exports = async (updater) => {
		const isDialogTableExist = await updater.isTableExists('b_im_dialog');
		if (isDialogTableExist === true)
		{
			const permissionsFieldExist = await updater
				.isColumnExists('b_im_dialog', 'permissions')
			;
			if (permissionsFieldExist === false)
			{
				updater.executeSql({
					query: 'ALTER TABLE b_im_dialog ADD COLUMN permissions TEXT',
				});
			}
		}
	};
});
