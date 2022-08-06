/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/lib/converter/dialog
 */
jn.define('im/messenger/lib/converter/dialog', (require, exports, module) => {

	const { Type, Loc } = jn.require('im/messenger/lib/core');
	const { MessengerParams } = jn.require('im/messenger/lib/params');

	/**
	 * @class DialogConverter
	 */
	class DialogConverter
	{
		constructor()
		{
			this.quoteSeparator = '------------------------------------------------------';
			this.mobileQuoteSeparator = '------------------';
		}

		toMessageList(modelMessageList)
		{
			return modelMessageList.map(modelMessage => this.toMessageItem(modelMessage));
		}

		toMessageItem(modelMessage)
		{
			const currentUserId = MessengerParams.getUserId();

			const messageItem = {
				id: modelMessage.id.toString(),
				username: modelMessage.author_name,
				time: this.toMessageTime(modelMessage.date),
				me: modelMessage.author_id === currentUserId,
				read: true,//TODO: !modelMessage.unread,
			};

			if (Type.isStringFilled(modelMessage.text))
			{
				messageItem.message = this.toMessageText(modelMessage.text);
			}

			if (!modelMessage.params)
			{
				return messageItem;
			}

			if (Type.isArrayFilled(modelMessage.params.FILE_ID))
			{
				messageItem.message = 'Dev: Pictures are not supported yet.';
			}

			if (Type.isArrayFilled(modelMessage.params.LIKE))
			{
				messageItem.likeCount = modelMessage.params.LIKE.length;
			}

			return messageItem;
		}

		toMessageText(text)
		{
			return text.replace(new RegExp(this.quoteSeparator,'g'), this.mobileQuoteSeparator);
		}

		toMessageTime(date)
		{
			if (!Type.isDate(date))
			{
				date = new Date(date);
			}

			if (Number.isNaN(date))
			{
				return '--:--';
			}

			const addZero = num => (num >= 0 && num <= 9) ? '0' + num : num;

			return date.getHours() + ':' + addZero(date.getMinutes());
		}

		toQuote(quotedMessage, commentText)
		{
			const lineBreak = '\n';

			return (
				this.quoteSeparator
				+ lineBreak
				+ quotedMessage.username
				+ lineBreak
				+ quotedMessage.message
				+ lineBreak
				+ this.quoteSeparator
				+ lineBreak
				+ commentText
			);
		}

		fromPushToMessage(params = {})
		{
			const message = {
				author_id: params.message.senderId,
				dialogId: params.dialogId,
				date: params.message.date,
				id: params.message.id,
				params: params.message.params,
				text: params.message.textOriginal,
				unread: params.message.unread,
				templateId: params.message.templateId,
			};

			const user = MessengerStore.getters['usersModel/getUserById'](message.author_id);

			message.author_name = (user && user.name) ? user.name : '';

			return message;
		}
	}

	module.exports = {
		DialogConverter: new DialogConverter(),
	};
});
