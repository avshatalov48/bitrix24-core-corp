/**
 * @module im/messenger/lib/event
 */
jn.define('im/messenger/lib/event', (require, exports, module) => {

	const { MessengerEvent } = jn.require('im/messenger/lib/event/messenger');

	module.exports = {
		MessengerEvent,
	};
});
