/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/lib/converter/dialog
 */
jn.define('im/messenger/lib/converter/dialog', (require, exports, module) => {

	const { Type } = jn.require('type');
	const { DialogHelper } = jn.require('im/messenger/lib/helper');
	const { TextMessage } = jn.require('im/messenger/lib/converter/dialog/message/text');
	const { UnsupportedMessage } = jn.require('im/messenger/lib/converter/dialog/message/unsupported');

	/**
	 * @class DialogConverter
	 */
	class DialogConverter
	{
		static toMessageList(modelMessageList)
		{
			return modelMessageList
				.map((modelMessage, index, modelMessageList) => {

					const options = {
						showUsername: true,
						showAvatar: true,
					};

					const isDialog = DialogHelper.isChatId(modelMessage.dialogId);
					const isMessageByTheSameAuthor = (
						modelMessageList[index + 1]
						&& modelMessageList[index + 1].authorId === modelMessage.authorId
					);

					if (isDialog || isMessageByTheSameAuthor)
					{
						options.showUsername = false;
						options.showAvatar = false;
					}

					return DialogConverter.toMessageItem(modelMessage, options);
				})
			;
		}

		static toMessageItem(modelMessage, options)
		{
			if (modelMessage.params && Type.isArrayFilled(modelMessage.params.FILE_ID))
			{
				return new UnsupportedMessage();
			}

			return new TextMessage(modelMessage, options);
		}

		static fromPushToMessage(params = {})
		{
			return {
				authorId: params.message.senderId,
				dialogId: params.dialogId,
				date: params.message.date,
				id: params.message.id,
				params: params.message.params,
				text: params.message.textOriginal,
				unread: params.message.unread,
				templateId: params.message.templateId,
			};
		}

		static toQuote(quotedMessage, commentText)
		{
			const quoteSeparator = '------------------------------------------------------';
			const lineBreak = '\n';

			return (
				quoteSeparator
				+ lineBreak
				+ quotedMessage.username
				+ lineBreak
				+ quotedMessage.message
				+ lineBreak
				+ quoteSeparator
				+ lineBreak
				+ commentText
			);
		}
	}

	module.exports = {
		DialogConverter,
	};
});
