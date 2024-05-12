/**
 * @module im/messenger/db/update/version/5
 */
jn.define('im/messenger/db/update/version/5', (require, exports, module) => {
	/**
	 * @param {Updater} updater
	 */
	module.exports = async (updater) => {
		const isDialogTableExist = await updater.isTableExists('b_im_dialog');
		if (isDialogTableExist === true)
		{
			const isAiProviderFieldExist = await updater
				.isColumnExists('b_im_dialog', 'aiProvider')
			;
			if (isAiProviderFieldExist === false)
			{
				updater.executeSql({
					query: 'ALTER TABLE b_im_dialog ADD COLUMN aiProvider TEXT',
				});
			}
		}
	};
});
