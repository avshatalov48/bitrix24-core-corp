/**
 * @module im/messenger-v2/controller/recent/service/empty-state/lib/welcome-screen/channel
 */
jn.define('im/messenger-v2/controller/recent/service/empty-state/lib/welcome-screen/channel', (require, exports, module) => {
	const { Loc } = require('im/messenger/loc');

	const { openChatCreateByActiveRecentTab } = require('im/messenger-v2/lib/open-chat-create');
	const { WelcomeScreen } = require('im/messenger-v2/lib/widget/chat-recent/welcome-screen');

	/**
	 * @class ChannelWelcomeScreen
	 */
	class ChannelWelcomeScreen
	{
		constructor()
		{
			this.welcomeScreen = WelcomeScreen.create()
				.setUpperText(Loc.getMessage('IMMOBILE_RECENT_SERVICE_EMPTY_STATE_CHANNEL_TITLE'))
				.setLowerText(Loc.getMessage('IMMOBILE_RECENT_SERVICE_EMPTY_STATE_CHANNEL_TEXT'))
				.setIconName('ws_channels')
				.setListener(openChatCreateByActiveRecentTab)
			;
		}

		toChatRecentWidgetItem()
		{
			return this.welcomeScreen.toChatRecentWidgetItem();
		}
	}

	module.exports = ChannelWelcomeScreen;
});
