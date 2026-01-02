/**
 * @module im/messenger/model/anchor
 */
jn.define('im/messenger/model/anchor', (require, exports, module) => {
	const { anchorModel } = require('im/messenger/model/anchor/model');
	const { anchorDefaultElement } = require('im/messenger/model/anchor/default-element');

	module.exports = {
		anchorModel,
		anchorDefaultElement,
	};
});
