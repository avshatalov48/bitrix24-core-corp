/**
 * @module im/messenger/lib/helper
 */
jn.define('im/messenger/lib/helper', (require, exports, module) => {
	const { DialogHelper } = require('im/messenger/lib/helper/dialog');
	const { UserHelper } = require('im/messenger/lib/helper/user');
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
		getFileIconTypeByExtension,
	} = require('im/messenger/lib/helper/file');
	const { EntitySelectorHelper } = require('im/messenger/lib/helper/entity-selector');

	module.exports = {
		DateHelper: new DateHelper(),
		DialogHelper,
		UserHelper,
		MessageHelper,
		Url,
		Worker,
		SoftLoader,
		formatFileSize,
		getShortFileName,
		getFileExtension,
		getFileTypeByExtension,
		getFileIconTypeByExtension,
		EntitySelectorHelper,
	};
});
