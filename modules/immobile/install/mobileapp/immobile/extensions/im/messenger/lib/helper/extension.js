/**
 * @module im/messenger/lib/helper
 */
jn.define('im/messenger/lib/helper', (require, exports, module) => {
	const { DialogHelper } = require('im/messenger/lib/helper/dialog');
	const { DateHelper } = require('im/messenger/lib/helper/date');
	const { MessageHelper } = require('im/messenger/lib/helper/message');
	const {
		Url,
	} = require('im/messenger/lib/helper/url');
	const { Worker } = require('im/messenger/lib/helper/worker');
	const { SoftLoader } = require('im/messenger/lib/helper/soft-loader');
	const {
		formatFileSize,
		getShortFileName,
		getFileExtension,
		getFileTypeByExtension,
	} = require('im/messenger/lib/helper/file');

	module.exports = {
		DateHelper: new DateHelper(),
		DialogHelper,
		MessageHelper,
		Url,
		Worker,
		SoftLoader,
		formatFileSize,
		getShortFileName,
		getFileExtension,
		getFileTypeByExtension,
	};
});
