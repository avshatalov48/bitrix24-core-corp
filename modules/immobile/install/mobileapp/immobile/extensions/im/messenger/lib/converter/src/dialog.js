/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/lib/converter/dialog
 */
jn.define('im/messenger/lib/converter/dialog', (require, exports, module) => {

	const { Type } = require('type');
	const { core } = require('im/messenger/core');
	const {
		FileType,
		DialogType,
	} = require('im/messenger/const');
	const { MessengerParams } = require('im/messenger/lib/params');
	const {
		TextMessage,
		DeletedMessage,
		ImageMessage,
		AudioMessage,
		VideoMessage,
		FileMessage,
		SystemTextMessage,
		UnsupportedMessage
	} = require('im/messenger/lib/element');

	/**
	 * @class DialogConverter
	 */
	class DialogConverter
	{
		static createMessageList(modelMessageList)
		{
			if (!Type.isArrayFilled(modelMessageList))
			{
				return [];
			}

			const options = {};

			const chatId = modelMessageList[0].chatId;
			const dialog = core.getStore().getters['dialoguesModel/getByChatId'](chatId);
			if (dialog.type === DialogType.user)
			{
				options.showUsername = false;
				options.showAvatar = false;
			}

			return modelMessageList.map((modelMessage) => DialogConverter.createMessage(modelMessage, options));
		}

		static createMessage(modelMessage = {}, options = {})
		{
			const isSystemMessage = modelMessage.authorId === 0;
			if (isSystemMessage)
			{
				return new SystemTextMessage(modelMessage, options);
			}

			const isDeletedMessage = modelMessage.params.IS_DELETED === 'Y';
			if (isDeletedMessage)
			{
				return new DeletedMessage(modelMessage, options);
			}

			const isMessageWithFile = modelMessage.files[0];
			let file = null;
			if (isMessageWithFile)
			{
				file = core.getStore().getters['filesModel/getById'](modelMessage.files[0]);
			}

			if (isMessageWithFile && file && file.type === FileType.image)
			{
				return new ImageMessage(modelMessage, options, file);
			}

			if (isMessageWithFile && file && file.type === FileType.audio)
			{
				return new AudioMessage(modelMessage, options, file);
			}

			if (isMessageWithFile && file && file.type === FileType.video)
			{
				return new VideoMessage(modelMessage, options, file);
			}

			if (isMessageWithFile && file && file.type === FileType.file)
			{
				return new FileMessage(modelMessage, options, file);
			}

			if (Type.isStringFilled(modelMessage.text))
			{
				return new TextMessage(modelMessage, options);
			}

			return new UnsupportedMessage(modelMessage, options);
		}

		static fromPushToMessage(params = {})
		{
			const modelMessage = {
				authorId: params.message.senderId,
				dialogId: params.dialogId,
				chatId: params.message.chatId,
				date: params.message.date,
				id: params.message.id,
				params: params.message.params,
				text: params.message.text,
				unread: params.message.unread,
				system: params.message.system,
			};

			if (modelMessage.authorId !== MessengerParams.getUserId())
			{
				modelMessage.unread = true;
				modelMessage.viewed = false;
			}
			else
			{
				modelMessage.unread = false;
			}

			return modelMessage;
		}
	}

	module.exports = {
		DialogConverter,
	};
});
