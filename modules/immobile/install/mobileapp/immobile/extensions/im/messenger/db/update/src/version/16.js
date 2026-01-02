/**
 * @module im/messenger/db/update/version/16
 */
jn.define('im/messenger/db/update/version/16', (require, exports, module) => {
	/**
	 * @param {Updater} updater
	 */
	module.exports = async (updater) => {
		const isDialogTableExist = await updater.isTableExists('b_im_dialog');
		if (isDialogTableExist === true)
		{
			const isTextFieldEnabledColumnExist = await updater
				.isColumnExists('b_im_dialog', 'textFieldEnabled')
			;

			if (isTextFieldEnabledColumnExist === false)
			{
				await updater.executeSql({
					query: 'ALTER TABLE b_im_dialog ADD COLUMN textFieldEnabled TEXT',
				});
			}

			const isBackgroundIdColumnExist = await updater
				.isColumnExists('b_im_dialog', 'backgroundId')
			;

			if (isBackgroundIdColumnExist === false)
			{
				await updater.executeSql({
					query: 'ALTER TABLE b_im_dialog ADD COLUMN backgroundId TEXT',
				});
			}
		}
	};
});
