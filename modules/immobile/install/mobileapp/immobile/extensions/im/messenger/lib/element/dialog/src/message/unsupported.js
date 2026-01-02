/**
 * @module im/messenger/lib/element/dialog/message/unsupported
 */
jn.define('im/messenger/lib/element/dialog/message/unsupported', (require, exports, module) => {
	const { Loc } = require('im/messenger/loc');
	const { Type } = require('type');
	const { Color } = require('tokens');

	const { Message } = require('im/messenger/lib/element/dialog/message/base');
	const { MessageType } = require('im/messenger/const');

	/**
	 * @class UnsupportedMessage
	 */
	class UnsupportedMessage extends Message
	{
		/**
		 * @param {MessagesModelState} modelMessage
		 * @param {CreateMessageOptions} options
		 */
		constructor(modelMessage = {}, options = {})
		{
			super(modelMessage, options);

			this.setMessage();
			this.setAvatarUnsupportedMessage();
		}

		getType()
		{
			return MessageType.text;
		}

		setAvatarUnsupportedMessage() {
			if (!Type.isStringFilled(this.username) && !Type.isStringFilled(this.avatarUrl))
			{
				this.avatarUrl = null;
				this.showAvatar = false;
				this.disableTailUnsupportedMessage();
			}
		}

		disableTailUnsupportedMessage() {
			this.disableTail();
			this.isAuthorBottomMessage = false;
			this.isAuthorTopMessage = false;
		}

		/**
		* @override
		*/
		setMessage()
		{
			const title = Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_UNSUPPORTED_TITLE');
			const subtitle = Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_UNSUPPORTED_SUBTITLE');
			const urlText = Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_UNSUPPORTED_LINK_MSGVER_1');
			const description = `[color=${Color.chatMyColorShade.toHex()}][b]${title}[/b]\n${subtitle}[/color]`;

			let url = 'https://play.google.com/store/apps/details?id=com.bitrix24.android';
			if (Application.getPlatform() === 'ios')
			{
				url = 'https://apps.apple.com/ru/app/bitrix24/id561683423';
			}

			const message = {
				text: `${description}\n\n[url=${url}]${urlText}[/url]`,
				type: 'text',
			};

			this.message = [message];
		}
	}

	module.exports = {
		UnsupportedMessage,
	};
});
