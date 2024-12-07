/**
 * @module im/messenger/lib/element/dialog/message/banner/banners/invite-users-copilot
 */
jn.define('im/messenger/lib/element/dialog/message/banner/banners/invite-users-copilot', (require, exports, module) => {
	const { BannerMessage } = require('im/messenger/lib/element/dialog/message/banner/message');
	const { Loc } = require('loc');

	class InviteUsersCopilotBanner extends BannerMessage
	{
		prepareTextMessage()
		{
			const matches = this.message[0].text.match(/\[user=\d+](.*?)\[\/user]/gim);
			if (matches && matches.length > 0)
			{
				const firstUser = matches[0].replaceAll('USER', 'COPILOT');
				this.message[0].text = firstUser;
				const otherCount = matches.length - 1;
				if (otherCount > 0)
				{
					this.message[0].text = Loc.getMessage(
						'IMMOBILE_ELEMENT_DIALOG_MESSAGE_COPILOT_BANNER_TEXT_ADD_USERS_MORE',
						{
							'#USERNAME_1#': firstUser,
							'#LINK_START#': '[COPILOT=sidebar]',
							'#USERS_COUNT#': otherCount,
							'#LINK_END#': '[/COPILOT]',
						},
					);
				}
			}
		}
	}

	module.exports = { InviteUsersCopilotBanner };
});
