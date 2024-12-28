/**
 * @module call/const/dialog-type
 */
jn.define('call/const/dialog-type', (require, exports, module) => {
	const DialogType = Object.freeze({
		user: 'user',
		chat: 'chat',
		videoconf: 'videoconf',
		collab: 'collab',
	});

	module.exports = { DialogType };
});
