/**
 * @module im/messenger/db/update/version/11
 */
jn.define('im/messenger/db/update/version/11', (require, exports, module) => {
	/**
	 * @param {Updater} updater
	 */
	module.exports = async (updater) => {
		const isMessageTableExist = await updater.isTableExists('b_im_message');
		if (isMessageTableExist === true)
		{
			const isKeyboardFieldExist = await updater
				.isColumnExists('b_im_message', 'keyboard')
			;

			if (isKeyboardFieldExist === false)
			{
				updater.executeSql({
					query: 'ALTER TABLE b_im_message ADD COLUMN keyboard TEXT',
				});
			}
		}
	};
});
