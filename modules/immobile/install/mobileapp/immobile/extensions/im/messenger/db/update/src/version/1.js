/**
 * @module im/messenger/db/update/version/1
 */
jn.define('im/messenger/db/update/version/1', (require, exports, module) => {

	/**
	 * @param {Updater} updater
	 */
	module.exports = async (updater) => {
		const isDialogTableExist = await updater.isTableExists('b_im_dialog');
		if (isDialogTableExist === true)
		{
			const isLastMessageViewsFieldExist = await updater
				.isColumnExists('b_im_dialog', 'lastMessageViews')
			;
			if (isLastMessageViewsFieldExist === false)
			{
				updater.executeSql({
					query: 'ALTER TABLE b_im_dialog ADD COLUMN lastMessageViews TEXT',
				});
			}
		}
	};
});
