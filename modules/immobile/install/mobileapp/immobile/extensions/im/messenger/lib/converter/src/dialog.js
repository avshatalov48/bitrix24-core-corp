/**
 * @module im/messenger/lib/converter/dialog
 */
jn.define('im/messenger/lib/converter/dialog', (require, exports, module) => {
	const { Type } = require('type');

	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const {
		DialogType,
		MessageParams,
	} = require('im/messenger/const');
	const {
		TextMessage,
		EmojiOnlyMessage,
		DeletedMessage,
		ImageMessage,
		MediaGalleryMessage,
		AudioMessage,
		VideoMessage,
		FileMessage,
		FileGalleryMessage,
		SystemTextMessage,
		UnsupportedMessage,
		CopilotPromptMessage,
		CopilotErrorMessage,
		CopilotMessage,
		CheckInMessageFactory,
		CreateBannerFactory,
		GalleryMessageFactory,
		CallMessageFactory,
	} = require('im/messenger/lib/element');
	const { Feature } = require('im/messenger/lib/feature');
	const { MessageHelper } = require('im/messenger/lib/helper');

	/**
	 * @class DialogConverter
	 */
	class DialogConverter
	{
		/**
		 * @param {Array<MessagesModelState>} modelMessageList
		 * @param dialogId
		 * @return {Array<Message>}
		 */
		static createMessageList(modelMessageList, dialogId)
		{
			if (!Type.isArrayFilled(modelMessageList))
			{
				return [];
			}

			const dialog = serviceLocator.get('core').getStore().getters['dialoguesModel/getById'](dialogId);
			const options = DialogConverter.prepareSharedOptionsForMessages(dialog);

			return modelMessageList.map((modelMessage) => DialogConverter.createMessage(modelMessage, options));
		}

		/**
		 * @param {MessagesModelState} modelMessage
		 * @param {CreateMessageOptions} options
		 * @return {Message}
		 */
		static createMessage(modelMessage = {}, options = {})
		{
			if (modelMessage.params?.componentId === MessageParams.ComponentId.ChatCopilotCreationMessage)
			{
				return new CopilotPromptMessage(modelMessage, options);
			}

			if (CreateBannerFactory.checkSuitableForDisplay(modelMessage))
			{
				return CreateBannerFactory.create(modelMessage, options);
			}

			const files = serviceLocator.get('core')
				.getStore()
				.getters['filesModel/getListByMessageId'](modelMessage.id)
			;
			const messageHelper = MessageHelper.createByModel(
				modelMessage,
				files,
			);

			if (messageHelper.isSystem)
			{
				return new SystemTextMessage(modelMessage, { ...options, showCommentInfo: false });
			}

			if (messageHelper.isDeleted)
			{
				return new DeletedMessage(modelMessage, options);
			}

			if (
				modelMessage.params?.COMPONENT_PARAMS?.copilotError
				|| modelMessage.params?.COMPONENT_PARAMS?.COPILOT_ERROR // TODO delete after fix on back
			)
			{
				return new CopilotErrorMessage(modelMessage, options);
			}

			if (modelMessage.params?.componentId === MessageParams.ComponentId.CopilotMessage)
			{
				return new CopilotMessage(modelMessage, options);
			}

			if (CheckInMessageFactory.checkSuitableForDisplay(modelMessage))
			{
				return CheckInMessageFactory.create(modelMessage, options);
			}

			if (messageHelper.isMediaGallery && Feature.isGalleryMessageSupported)
			{
				return new MediaGalleryMessage(modelMessage, options, files);
			}

			if (messageHelper.isFileGallery && Feature.isGalleryMessageSupported)
			{
				return new FileGalleryMessage(modelMessage, options, files);
			}

			if (GalleryMessageFactory.checkSuitableForDisplay(modelMessage))
			{
				return GalleryMessageFactory.create(modelMessage, options);
			}

			if (CallMessageFactory.checkSuitableForDisplay(modelMessage))
			{
				return CallMessageFactory.create(modelMessage, options);
			}

			const file = files[0];
			if (messageHelper.isImage)
			{
				if (Type.isStringFilled(file?.urlPreview))
				{
					return new ImageMessage(modelMessage, options, file);
				}

				return new FileMessage(modelMessage, options, file);
			}

			if (messageHelper.isAudio)
			{
				return new AudioMessage(modelMessage, options, file);
			}

			if (messageHelper.isVideo)
			{
				return new VideoMessage(modelMessage, options, file);
			}

			if (messageHelper.isFile)
			{
				return new FileMessage(modelMessage, options, file);
			}

			if (messageHelper.isWithAttach)
			{
				return new TextMessage(modelMessage, options);
			}

			if (messageHelper.isEmojiOnly || messageHelper.isSmileOnly)
			{
				return new EmojiOnlyMessage(modelMessage, options);
			}

			if (messageHelper.isText)
			{
				return new TextMessage(modelMessage, options);
			}

			return new UnsupportedMessage(modelMessage, options);
		}

		static createMessageFromRecent(dialogId)
		{
			const recentItem = serviceLocator.get('core').getStore().getters['recentModel/getById'](dialogId);
			if (!recentItem || !recentItem.message || !recentItem.message.text)
			{
				return null;
			}

			const recentMessage = recentItem.message;

			const defaultOptions = {
				showUsername: false,
				showAvatar: false,
			};

			const defaultModelMessage = {
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

			const dialog = serviceLocator.get('core').getStore().getters['dialoguesModel/getById'](dialogId);

			const options = dialog
				? DialogConverter.prepareSharedOptionsForMessages(dialog)
				: defaultOptions
			;

			const storedMessage = serviceLocator.get('core').getStore().getters['messagesModel/getById'](recentMessage.id);

			const modelMessage = ('id' in storedMessage)
				? storedMessage
				: defaultModelMessage
			;

			return DialogConverter.createMessage(modelMessage, options);
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

			if (modelMessage.authorId === serviceLocator.get('core').getUserId())
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

		/**
		 *
		 * @param {DialoguesModelState} dialog
		 * @return {CreateMessageOptions}
		 */
		static prepareSharedOptionsForMessages(dialog)
		{
			/** @type {CreateMessageOptions} */
			const options = {};
			if (dialog.type === DialogType.user)
			{
				options.showUsername = false;
				options.showAvatar = false;
			}

			if (dialog.type === DialogType.copilot)
			{
				options.canBeQuoted = false;
				options.canBeChecked = false;
			}

			if ([DialogType.openChannel, DialogType.channel, DialogType.generalChannel].includes(dialog.type))
			{
				options.showCommentInfo = true;
				options.showAvatarsInReaction = false;
			}

			if (dialog.type === DialogType.comment)
			{
				options.initialPostMessageId = String(dialog.parentMessageId);
			}

			const applicationSettingState = serviceLocator.get('core').getStore().getters['applicationModel/getSettings']();
			options.audioRate = applicationSettingState ? applicationSettingState.audioRate : 1;
			options.dialogId = dialog.dialogId;

			return options;
		}
	}

	module.exports = {
		DialogConverter,
	};
});
