/**
 * @module im/messenger/const/navigation-tab
 */
jn.define('im/messenger/const/navigation-tab', (require, exports, module) => {
	const { ComponentCode } = require('im/messenger/const/component-code');

	/**
	 * @deprecated
	 */
	const NavigationTab = {
		imMessenger: 'chats',
		imCopilotMessenger: 'copilot',
		imChannelMessenger: 'channel',
		imCollabMessenger: 'collab',
		imNotify: 'notifications',
		imOpenlinesRecent: 'openlines',
	};

	const NavigationTabId = {
		chats: 'chats',
		copilot: 'copilot',
		channel: 'channel',
		collab: 'collab',
		openlines: 'openlines',
		task: 'task',
	};

	const NavigationTabByComponent = {
		[ComponentCode.imMessenger]: NavigationTab.imMessenger,
		[ComponentCode.imChannelMessenger]: NavigationTab.imChannelMessenger,
		[ComponentCode.imCopilotMessenger]: NavigationTab.imCopilotMessenger,
		[ComponentCode.imCollabMessenger]: NavigationTab.imCollabMessenger,
		[ComponentCode.imNotify]: NavigationTab.imNotify,
		[ComponentCode.imOpenlinesRecent]: NavigationTab.imOpenlinesRecent,
	};

	module.exports = {
		NavigationTab,
		NavigationTabId,
		NavigationTabByComponent,
	};
});
