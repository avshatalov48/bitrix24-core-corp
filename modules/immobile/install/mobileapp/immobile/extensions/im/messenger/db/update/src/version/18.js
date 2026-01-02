/**
 * @module im/messenger/db/update/version/18
 */
jn.define('im/messenger/db/update/version/18', (require, exports, module) => {
	const {
		DraftTable,
		ReadMessageQueueTable,
		CommentTable,
		VoteTable,
	} = require('im/messenger/db/table');

	/**
	 * @param {Updater} updater
	 */
	module.exports = async (updater) => {
		new DraftTable().createDatabaseTableInstance();
		new ReadMessageQueueTable().createDatabaseTableInstance();
		new CommentTable().createDatabaseTableInstance();
		new VoteTable().createDatabaseTableInstance();
	};
});