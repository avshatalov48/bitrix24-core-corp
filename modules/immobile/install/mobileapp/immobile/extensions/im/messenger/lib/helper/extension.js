/**
 * @module im/messenger/lib/helper
 */
jn.define('im/messenger/lib/helper', (require, exports, module) => {
	const { DialogHelper } = require('im/messenger/lib/helper/dialog');
	const { DateHelper } = require('im/messenger/lib/helper/date');
	const { Worker } = require('im/messenger/lib/helper/worker');
	const { SoftLoader } = require('im/messenger/lib/helper/soft-loader');
	const {
		formatFileSize,
		getShortFileName,
		getFileTypeByExtension,
	} = require('im/messenger/lib/helper/file');

	module.exports = {
		DialogHelper: new DialogHelper(),
		DateHelper: new DateHelper(),
		Worker,
		SoftLoader,
		formatFileSize,
		getShortFileName,
		getFileTypeByExtension,
	};
});
