/**
 * @module im/messenger/db/update/version/3
 */
jn.define('im/messenger/db/update/version/3', (require, exports, module) => {
	/**
	 * @param {Updater} updater
	 */
	module.exports = async (updater) => {
		const isMessageTableExist = await updater.isTableExists('b_im_message');
		if (isMessageTableExist === true)
		{
			const isForwardFieldExist = await updater
				.isColumnExists('b_im_message', 'forward')
			;

			if (isForwardFieldExist === false)
			{
				updater.executeSql({
					query: 'ALTER TABLE b_im_message ADD COLUMN forward TEXT',
				});
			}
		}
	};
});
