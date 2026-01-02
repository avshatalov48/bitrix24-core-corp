/**
 * @module im/messenger-v2/lib/open-chat-create
 */
jn.define('im/messenger-v2/lib/open-chat-create', (require, exports, module) => {
	const { NavigationTabId } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { CreateChannel } = require('im/messenger/controller/chat-composer');

	async function openChatCreateByActiveRecentTab()
	{
		const { RecentManager } = await requireLazy('im:messenger-v2/controller/recent/manager');

		const tabId = RecentManager.getInstance().getActiveRecent().id;
		const openChatCreateCollection = {
			[NavigationTabId.chats]: openChatCreate,
			[NavigationTabId.copilot]: openCopilotCreate,
			[NavigationTabId.channel]: openChannelCreate,
			[NavigationTabId.collab]: openCollabCreate,
		};

		if (openChatCreateCollection[tabId])
		{
			openChatCreateCollection[tabId]();

			return;
		}

		openChatCreateCollection[NavigationTabId.chats]();
	}

	function openChatCreate()
	{
		void serviceLocator.get('dialog-creator').open();
	}

	function openCopilotCreate()
	{
		serviceLocator.get('dialog-creator').createCopilotDialog();
	}

	function openChannelCreate()
	{
		const createChannel = new CreateChannel();
		void createChannel.open();
	}

	function openCollabCreate()
	{
		void serviceLocator.get('dialog-creator').createCollab();
	}

	module.exports = {
		openChatCreateByActiveRecentTab,
		openChatCreate,
		openCopilotCreate,
		openChannelCreate,
		openCollabCreate,
	};
});
