/**
 * @module im/messenger/controller/chat-composer/lib/const
 */
jn.define('im/messenger/controller/chat-composer/lib/const', (require, exports, module) => {
	const ComposerDialogType = Object.freeze({
		groupChat: 'groupChat',
		channel: 'channel',
	});

	module.exports = { ComposerDialogType };
});
