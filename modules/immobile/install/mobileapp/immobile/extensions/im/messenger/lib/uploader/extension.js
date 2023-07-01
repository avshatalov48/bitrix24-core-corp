/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-pseudo-private */

/**
 * @module im/messenger/lib/uploader
 */
jn.define('im/messenger/lib/uploader', (require, exports, module) => {
	const {
		Uploader,
		uploader,
	} = require('im/messenger/lib/uploader/uploader');
	const { UploadTask } = require('im/messenger/lib/uploader/task');

	module.exports = {
		Uploader,
		uploader,
		UploadTask,
	};
});
