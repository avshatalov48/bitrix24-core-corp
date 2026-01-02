/**
 * @module im/messenger-v2/provider/pull/message
 */
jn.define('im/messenger-v2/provider/pull/message', (require, exports, module) => {
	const { MessagePullHandler } = require('im/messenger-v2/provider/pull/message/handler');

	module.exports = { MessagePullHandler };
});
