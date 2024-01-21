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
	const { emojiRegex } = require('im/messenger/lib/utils');
	const {
		TextMessage,
		EmojiOnlyMessage,
		DeletedMessage,
		ImageMessage,
		AudioMessage,
		VideoMessage,
		FileMessage,
		SystemTextMessage,
		UnsupportedMessage,
	} = require('im/messenger/lib/element');
	const { SmileManager } = require('im/messenger/lib/smile-manager');

	/**
	 * @class DialogConverter
	 */
	class DialogConverter
	{
		/**
		 * @param {Array<MessagesModelState>} modelMessageList
		 * @return {Array<Message>}
		 */
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

		/**
		 * @param {MessagesModelState} modelMessage
		 * @param {CreateMessageOptions}options
		 * @return {Message}
		 */
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
			/** @type {FilesModelState || null} */
			let file = null;
			if (isMessageWithFile)
			{
				file = core.getStore().getters['filesModel/getById'](modelMessage.files[0]);
			}

			if (isMessageWithFile && file && file.type === FileType.image)
			{
				if (Type.isStringFilled(file.urlPreview))
				{
					return new ImageMessage(modelMessage, options, file);
				}

				return new FileMessage(modelMessage, options, file);
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

			const isMessageWithAttach = modelMessage
				&& modelMessage.params
				&& modelMessage.params.ATTACH
				&& modelMessage.params.ATTACH[0]
			;

			if (isMessageWithAttach)
			{
				return new TextMessage(modelMessage, options);
			}

			if (
				Type.isStringFilled(modelMessage.text)
				&& (DialogConverter.isEmojiOnly(modelMessage.text) || DialogConverter.isSmileOnly(modelMessage.text))
			)
			{
				return new EmojiOnlyMessage(modelMessage, options);
			}

			if (Type.isStringFilled(modelMessage.text))
			{
				return new TextMessage(modelMessage, options);
			}

			return new UnsupportedMessage(modelMessage, options);
		}

		static createRecentMessage(dialogId)
		{
			const recentItem = core.getStore().getters['recentModel/getById'](dialogId);
			if (!recentItem || !recentItem.message || !recentItem.message.text)
			{
				return null;
			}

			const recentMessage = recentItem.message;

			const options = {
				showUsername: false,
				showAvatar: false,
			};

			const modelMessage = {
				id: recentMessage.id,
				templateId: '',
				chatId: 0,
				authorId: recentMessage.senderId,
				date: recentMessage.date,
				text: recentMessage.text,
				loadText: '',
				params: {},
				replaces: [],
				files: [],
				unread: false,
				viewed: true,
				viewedByOthers: false,
				sending: false,
				error: false,
				errorReason: 0,
				retry: false,
				audioPlaying: false,
				playingTime: 0,
			};

			let viewMessage = null;
			const isSystemMessage = recentMessage.senderId === 0;
			if (isSystemMessage)
			{
				viewMessage = new SystemTextMessage(modelMessage, options);
			}
			else if (Type.isStringFilled(modelMessage.text) && DialogConverter.isEmojiOnly(modelMessage.text))
			{
				viewMessage = new EmojiOnlyMessage(modelMessage, options);
			}
			else if (Type.isStringFilled(modelMessage.text))
			{
				viewMessage = new TextMessage(modelMessage, options);
			}

			if (viewMessage)
			{
				viewMessage.setShowTail(true);
				viewMessage.setAvatarUri(null);
			}

			return viewMessage;
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
			};

			if (modelMessage.authorId === MessengerParams.getUserId())
			{
				modelMessage.unread = false;
			}
			else
			{
				modelMessage.unread = true;
				modelMessage.viewed = false;
			}

			return modelMessage;
		}

		static isEmojiOnly(messageText)
		{
			const text = messageText.replaceAll(emojiRegex, '');

			return text.replaceAll(/\s/g, '').length === 0;
		}

		static isSmileOnly(messageText)
		{
			const smileManager = SmileManager.getInstance();
			if (Object.values(smileManager.getSmiles()).length === 0)
			{
				return false;
			}

			const pattern = smileManager.getPattern();
			const regExp = new RegExp(`(?:(?:${pattern})(?=(?:(?:${pattern})|\\s|&quot;|<|$)))`, 'g');
			const text = messageText.replaceAll(regExp, '');

			return text.replaceAll(/\s/g, '').length === 0;
		}
	}

	module.exports = {
		DialogConverter,
	};
});
