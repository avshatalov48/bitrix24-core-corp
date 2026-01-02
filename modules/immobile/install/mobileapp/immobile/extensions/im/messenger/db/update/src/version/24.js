/**
 * @module im/messenger/db/update/version/24
 */
jn.define('im/messenger/db/update/version/24', (require, exports, module) => {
	const { FileTable, TranscriptTable } = require('im/messenger/db/table');

	/**
	 * @param {Updater} updater
	 */
	module.exports = async (updater) => {
		const isFileTableExist = await updater.isTableExists('b_im_file');

		if (isFileTableExist === true)
		{
			await updater.addColumnIfNotExists(FileTable, 'isTranscribable');
		}

		new TranscriptTable().createDatabaseTableInstance();
	};
});
