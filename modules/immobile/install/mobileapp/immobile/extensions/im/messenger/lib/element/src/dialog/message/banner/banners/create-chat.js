/**
 * @module im/messenger/lib/element/dialog/message/banner/banners/create-chat
 */
jn.define('im/messenger/lib/element/dialog/message/banner/banners/create-chat', (require, exports, module) => {
	const { BannerMessage } = require('im/messenger/lib/element/dialog/message/banner/message');
	const { Loc } = require('loc');
	const { Theme } = require('im/lib/theme');

	class CreateChatBanner extends BannerMessage
	{
		prepareTextMessage()
		{
			const desc = Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_CHAT_CREATE_BANNER_DESC');
			this.message[0].text = `[color=${Theme.colors.base3}]${desc}[/color]\n\n[USER=sidebar]${Loc.getMessage(
				'IMMOBILE_ELEMENT_DIALOG_MESSAGE_CHAT_CREATE_BANNER_ADD_USERS')}[/USER]`;
		}
	}

	module.exports = { CreateChatBanner };
});
