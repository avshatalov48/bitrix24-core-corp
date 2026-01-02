/**
 * @module im/messenger/model/counter
 */
jn.define('im/messenger/model/counter', (require, exports, module) => {
	const { counterModel } = require('im/messenger/model/counter/model');
	const { counterDefaultElement } = require('im/messenger/model/counter/default-element');

	module.exports = {
		counterModel,
		counterDefaultElement,
	};
});
