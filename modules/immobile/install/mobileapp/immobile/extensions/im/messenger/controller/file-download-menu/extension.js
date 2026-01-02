/**
 * @module im/messenger/controller/file-download-menu
 */
jn.define('im/messenger/controller/file-download-menu', (require, exports, module) => {
	const { FileDownloadMenu } = require('im/messenger/controller/file-download-menu/src/file-download');
	const { ServerDownloadMenu } = require('im/messenger/controller/file-download-menu/src/server-download');

	module.exports = {
		FileDownloadMenu,
		ServerDownloadMenu,
	};
});
