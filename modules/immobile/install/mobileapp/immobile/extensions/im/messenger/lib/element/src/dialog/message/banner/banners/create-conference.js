/**
 * @module im/messenger/lib/element/dialog/message/banner/banners/create-conference
 */
jn.define('im/messenger/lib/element/dialog/message/banner/banners/create-conference', (require, exports, module) => {
	const { BannerMessage } = require('im/messenger/lib/element/dialog/message/banner/message');
	const { Loc } = require('loc');
	const { Theme } = require('im/lib/theme');

	class CreateChatConferenceBanner extends BannerMessage
	{
		prepareTextMessage()
		{
			const desc = Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_CHAT_CONFERENCE_CREATE_BANNER_DESC');
			this.message[0].text = `[color=${Theme.colors.base3}]${desc}[/color]\n\n[USER=copy:id${this.id}]${Loc.getMessage(
				'IMMOBILE_ELEMENT_DIALOG_MESSAGE_CHAT_CONFERENCE_CREATE_BANNER_COPY_LINK')}[/USER]`;
		}
	}

	module.exports = { CreateChatConferenceBanner };
});
