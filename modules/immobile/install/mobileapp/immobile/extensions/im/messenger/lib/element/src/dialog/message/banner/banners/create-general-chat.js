/**
 * @module im/messenger/lib/element/dialog/message/banner/banners/create-general-chat
 */
jn.define('im/messenger/lib/element/dialog/message/banner/banners/create-general-chat', (require, exports, module) => {
	const { BannerMessage } = require('im/messenger/lib/element/dialog/message/banner/message');
	const { Loc } = require('loc');

	class CreateGeneralChatBanner extends BannerMessage
	{
		prepareTextMessage()
		{
			const desc1 = Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_CHAT_GENERAL_CREATE_BANNER_DESC_1');
			const desc2 = Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_CHAT_GENERAL_CREATE_BANNER_DESC_2');
			const desc3 = Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_CHAT_GENERAL_CREATE_BANNER_DESC_3');
			this.message[0].text = `${desc1}\n\n${desc2}\n\n${desc3}`;
		}
	}

	module.exports = { CreateGeneralChatBanner };
});
