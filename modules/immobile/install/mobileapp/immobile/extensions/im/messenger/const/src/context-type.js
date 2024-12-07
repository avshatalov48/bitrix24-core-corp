/**
 * @module im/messenger/const/context-type
 */
jn.define('im/messenger/const/context-type', (require, exports, module) => {
	const OpenDialogContextType = {
		link: 'link',
		forward: 'forward',
		mention: 'mention',
		push: 'push',
		chatCreation: 'chatCreation',

		default: 'default',
	};

	module.exports = { OpenDialogContextType };
});
