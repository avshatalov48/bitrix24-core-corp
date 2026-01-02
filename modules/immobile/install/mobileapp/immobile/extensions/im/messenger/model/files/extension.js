/**
 * @module im/messenger/model/files
 */
jn.define('im/messenger/model/files', (require, exports, module) => {
	const { filesModel } = require('im/messenger/model/files/model');
	const { fileDefaultElement } = require('im/messenger/model/files/default-element');

	module.exports = {
		filesModel,
		fileDefaultElement,
	};
});
