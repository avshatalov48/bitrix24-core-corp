/**
 * @module im/messenger/model/queue
 */
jn.define('im/messenger/model/queue', (require, exports, module) => {
	const { queueModel } = require('im/messenger/model/queue/model');
	const { queueDefaultElement } = require('im/messenger/model/queue/default-element');

	module.exports = {
		queueModel,
		queueDefaultElement,
	};
});
