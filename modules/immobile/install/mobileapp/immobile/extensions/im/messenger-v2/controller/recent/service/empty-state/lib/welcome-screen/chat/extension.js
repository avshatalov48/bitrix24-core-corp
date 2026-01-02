/**
 * @module im/messenger-v2/controller/recent/service/empty-state/lib/welcome-screen/chat
 */
jn.define('im/messenger-v2/controller/recent/service/empty-state/lib/welcome-screen/chat', (require, exports, module) => {
	const { Loc } = require('im/messenger/loc');
	const { AnalyticsEvent } = require('analytics');

	const { Feature } = require('im/messenger/lib/feature');
	const { openChatCreateByActiveRecentTab } = require('im/messenger-v2/lib/open-chat-create');
	const { WelcomeScreen } = require('im/messenger-v2/lib/widget/chat-recent/welcome-screen');

	/**
	 * @class ChatWelcomeScreen
	 */
	class ChatWelcomeScreen
	{
		constructor()
		{
			let options = {};
			if (Feature.isIntranetInvitationAvailable)
			{
				options = {
					upperText: Loc.getMessage('IMMOBILE_RECENT_SERVICE_EMPTY_STATE_CHAT_TITLE'),
					lowerText: Loc.getMessage('IMMOBILE_RECENT_SERVICE_EMPTY_STATE_CHAT_TEXT_INVITE'),
					iconName: 'ws_employees',
					listener: ChatWelcomeScreen.openIntranetInvite,
				};
			}
			else
			{
				options = {
					upperText: Loc.getMessage('IMMOBILE_RECENT_SERVICE_EMPTY_STATE_CHAT_TEXT_INVITE'),
					lowerText: Loc.getMessage('IMMOBILE_RECENT_SERVICE_EMPTY_STATE_CHAT_TEXT_CREATE'),
					iconName: 'ws_employees',
					listener: openChatCreateByActiveRecentTab,
				};
			}

			options.startChatButton = {
				text: Loc.getMessage('IMMOBILE_RECENT_SERVICE_EMPTY_STATE_CHAT_BUTTON'),
				iconName: 'ws_plus',
			};

			this.welcomeScreen = WelcomeScreen.create(options);
		}

		static openIntranetInvite = () => {
			const { openIntranetInviteWidget } = require('intranet/invite-opener-new');
			openIntranetInviteWidget?.({
				analytics: new AnalyticsEvent().setSection('chat'),
			});
		};

		toChatRecentWidgetItem()
		{
			return this.welcomeScreen.toChatRecentWidgetItem();
		}
	}

	module.exports = ChatWelcomeScreen;
});
