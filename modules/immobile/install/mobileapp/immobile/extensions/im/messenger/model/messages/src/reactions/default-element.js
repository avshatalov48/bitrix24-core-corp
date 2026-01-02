/**
 * @module im/messenger/model/messages/reactions/default-element
 */

jn.define('im/messenger/model/messages/reactions/default-element', (require, exports, module) => {
	const reactionDefaultElement = Object.freeze({
		dialogId: '',
		messageId: 0,
		ownReactions: new Set(),
		reactionCounters: {},
		reactionUsers: new Map(),
	});

	module.exports = {
		reactionDefaultElement,
	};
});
