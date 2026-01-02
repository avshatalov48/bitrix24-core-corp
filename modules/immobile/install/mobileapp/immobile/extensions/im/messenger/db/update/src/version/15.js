/**
 * @module im/messenger/db/update/version/15
 */
jn.define('im/messenger/db/update/version/15', (require, exports, module) => {
	/**
	 * @param {Updater} updater
	 */
	module.exports = async (updater) => {
		const isDialogTableExist = await updater.isTableExists('b_im_dialog');
		if (isDialogTableExist === true)
		{
			const isOptionalParamsColumnExist = await updater
				.isColumnExists('b_im_dialog', 'optionalParams')
			;

			if (isOptionalParamsColumnExist === false)
			{
				await updater.executeSql({
					query: 'ALTER TABLE b_im_dialog ADD COLUMN optionalParams JSON',
				});
			}
		}
	};
});
