/**
 * @module im/messenger/db/update/version/23
 */
jn.define('im/messenger/db/update/version/23', (require, exports, module) => {
	const { DialogTable } = require('im/messenger/db/table');

	/**
	 * @param {Updater} updater
	 */
	module.exports = async (updater) => {
		const isDialogTableExist = await updater.isTableExists('b_im_dialog');
		if (isDialogTableExist === true)
		{
			await updater.addColumnIfNotExists(DialogTable, 'entityLink');
		}
	};
});
