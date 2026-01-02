/**
 * @module im/messenger/db/update/version/19
 */
jn.define('im/messenger/db/update/version/19', (require, exports, module) => {
	/**
	 * @param {Updater} updater
	 */
	module.exports = async (updater) => {
		const isFileTableExist = await updater.isTableExists('b_im_file');
		if (isFileTableExist === true)
		{
			const isMediaUrlFieldExist = await updater
				.isColumnExists('b_im_file', 'mediaUrl')
			;

			if (isMediaUrlFieldExist === false)
			{
				updater.executeSql({
					query: 'ALTER TABLE b_im_file ADD COLUMN mediaUrl TEXT',
				});
			}
		}
	};
});
