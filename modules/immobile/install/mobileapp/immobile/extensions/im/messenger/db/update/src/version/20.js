/**
 * @module im/messenger/db/update/version/20
 */
jn.define('im/messenger/db/update/version/20', (require, exports, module) => {
	/**
	 * @param {Updater} updater
	 */
	module.exports = async (updater) => {
		const isSyncServiceLastIdValueExist = await updater
			.getRowsCountWithValue('b_im_option', 'name', 'SYNC_SERVICE_LAST_ID')
			> 0
		;

		if (isSyncServiceLastIdValueExist)
		{
			await updater.deleteRowsWithValue('b_im_option', 'name', 'SYNC_SERVICE_LAST_ID');
		}
	};
});
