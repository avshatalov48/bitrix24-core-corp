/**
 * @module im/messenger/lib/element/dialog/message/unsupported
 */
jn.define('im/messenger/lib/element/dialog/message/unsupported', (require, exports, module) => {
	const { Message } = require('im/messenger/lib/element/dialog/message/base');
	const { MessageType } = require('im/messenger/const');
	const { Loc } = require('loc');
	const { Type } = require('type');

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
			if (Application.getApiVersion() >= 53)
			{
				return MessageType.unsupported;
			}

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
			const title = Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_UNSUPPORTED');
			const text = Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_UNSUPPORTED_LINK');
			let url = 'https://play.google.com/store/apps/details?id=com.bitrix24.android';
			if (Application.getPlatform() === 'ios')
			{
				url = 'https://apps.apple.com/ru/app/bitrix24/id561683423';
			}

			const message = {
				text: `${title}\n[url=${url}]${text}[/url]`,
				type: 'text',
			};

			if (Application.getApiVersion() >= 53)
			{
				message.title = title;
				message.text = `[url=${url}]${text}[/url]`;
			}

			this.message = [message];
		}
	}

	module.exports = {
		UnsupportedMessage,
	};
});
