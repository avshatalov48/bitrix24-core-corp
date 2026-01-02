/**
 * @module im/messenger/model/draft
 */
jn.define('im/messenger/model/draft', (require, exports, module) => {
	const { draftModel } = require('im/messenger/model/draft/src/model');
	const { draftDefaultElement } = require('im/messenger/model/draft/src/default-element');

	module.exports = {
		draftModel,
		draftDefaultElement,
	};
});
