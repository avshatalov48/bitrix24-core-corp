/**
 * @module im/messenger/const/navigation-tab
 */
jn.define('im/messenger/const/navigation-tab', (require, exports, module) => {
	const NavigationTab = {
		imMessenger: 'chats',
		imCopilotMessenger: 'copilot',
		imChannelMessenger: 'channel',
		imCollabMessenger: 'collab',
		imNotify: 'notifications',
		imOpenlinesRecent: 'openlines',
	};

	module.exports = { NavigationTab };
});
