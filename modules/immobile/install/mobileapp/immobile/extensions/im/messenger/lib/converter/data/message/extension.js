/**
 * @module im/messenger/lib/converter/data/message
 */
jn.define('im/messenger/lib/converter/data/message', (require, exports, module) => {
	const { Type } = require('type');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	/**
	 * @class MessageDataConverter
	 */
	class MessageDataConverter
	{
		static fromPullToMessage(params = {})
		{
			return MessageDataConverter.fromPushToMessage(params);
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
				forward: params.message.forward || {},
				prevId: params.message.prevId,
			};

			if (Type.isBoolean(params.message.unread))
			{
				modelMessage.unread = params.message.unread;
			}

			if (Type.isBoolean(params.message.viewed))
			{
				modelMessage.viewed = params.message.viewed;
			}

			if (modelMessage.authorId === serviceLocator.get('core').getUserId())
			{
				modelMessage.unread ??= false;
			}
			else
			{
				modelMessage.unread ??= true;
				modelMessage.viewed ??= false;
			}

			return modelMessage;
		}
	}

	module.exports = { MessageDataConverter };
});
