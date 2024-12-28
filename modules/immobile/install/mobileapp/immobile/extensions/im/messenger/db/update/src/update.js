/**
 * @module im/messenger/db/update/update
 */
jn.define('im/messenger/db/update/update', (require, exports, module) => {
	const { Version } = require('im/messenger/db/update/version');

	const updateDatabase = async () => {
		const version = new Version();
		window.imMessengerVersion = version;
		window.imMessengerUpdater = version.getUpdater();

		await version.execute(1);
		await version.execute(2);
		await version.execute(3);
		await version.execute(4);
		await version.execute(5);
		await version.execute(6);
		await version.execute(7);
		await version.execute(8);
		await version.execute(9);
		await version.execute(10);
		await version.execute(11);
		await version.execute(12);
		await version.execute(13);
	};

	module.exports = {
		updateDatabase,
	};
});
