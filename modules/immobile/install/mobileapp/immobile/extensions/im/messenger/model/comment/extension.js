/**
 * @module im/messenger/model/comment
 */
jn.define('im/messenger/model/comment', (require, exports, module) => {
	const { commentModel } = require('im/messenger/model/comment/model');
	const { commentDefaultElement } = require('im/messenger/model/comment/default-element');

	module.exports = {
		commentModel,
		commentDefaultElement,
	};
});
