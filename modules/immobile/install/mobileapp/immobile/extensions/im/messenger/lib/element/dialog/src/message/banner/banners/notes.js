/**
 * @module im/messenger/lib/element/dialog/message/banner/banners/notes
 */
jn.define('im/messenger/lib/element/dialog/message/banner/banners/notes', (require, exports, module) => {
	const { BannerMessage } = require('im/messenger/lib/element/dialog/message/banner/message');
	const { Loc } = require('im/messenger/loc');
	const { Theme } = require('im/lib/theme');

	class NotesChatBanner extends BannerMessage
	{
		prepareTextMessage()
		{
			const desc = Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_CHAT_NOTES_BANNER_DESC');
			this.message[0].text = `[color=${Theme.colors.base3}]${desc}[/color]`;
		}
	}

	module.exports = { NotesChatBanner };
});
