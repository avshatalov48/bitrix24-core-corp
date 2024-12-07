/**
 * @module im/messenger/const/component-code
 */
jn.define('im/messenger/const/component-code', (require, exports, module) => {
	const ComponentCode = Object.freeze({
		imNavigation: 'im.navigation',
		imMessenger: 'im.messenger',
		imCopilotMessenger: 'im.copilot.messenger',
		imChannelMessenger: 'im.channel.messenger',
		imCollabMessenger: 'im.collab.messenger',
		imNotify: 'im.notify',
		imOpenlinesRecent: 'im.openlines.recent',
	});

	module.exports = { ComponentCode };
});
