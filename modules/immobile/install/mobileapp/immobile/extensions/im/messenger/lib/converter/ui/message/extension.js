/**
 * @module im/messenger/lib/converter/ui/message
 */
jn.define('im/messenger/lib/converter/ui/message', (require, exports, module) => {
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
		ErrorMessage,
		CopilotMessage,
		CheckInMessageFactory,
		CreateBannerFactory,
		GalleryMessageFactory,
		CallMessageFactory,
		VoteMessageFactory,
		AiAssistantMessage,
	} = require('im/messenger/lib/element/dialog');
	const { Feature } = require('im/messenger/lib/feature');
	const { MessageHelper } = require('im/messenger/lib/helper');

	/**
	 * @class MessageUiConverter
	 */
	class MessageUiConverter
	{
		/**
		 * @constructor
		 * @param {DialogId} dialogId
		 * @param {DialogLocator} dialogCode
		 */
		constructor({ dialogId, dialogCode })
		{
			this.dialogCode = dialogCode;
			this.dialogId = dialogId;
		}

		/**
		 * @param {Array<MessagesModelState>} modelMessageList
		 * @return {Array<Message>}
		 */
		createMessageList(modelMessageList)
		{
			if (!Type.isArrayFilled(modelMessageList))
			{
				return [];
			}

			const dialog = serviceLocator.get('core').getStore().getters['dialoguesModel/getById'](this.dialogId);
			const options = this.prepareSharedOptionsForMessages(dialog);

			return modelMessageList.map((modelMessage) => this.createMessage(modelMessage, options));
		}

		/**
		 * @param {MessagesModelState} modelMessage
		 * @param {CreateMessageOptions} options
		 * @return {Message}
		 */
		createMessage(modelMessage = {}, options = {})
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

			if (messageHelper.isAiAssistant && Feature.isAiAssistantMessageSupported)
			{
				return new AiAssistantMessage(modelMessage, options);
			}

			if (messageHelper.isSystem)
			{
				return new SystemTextMessage(modelMessage, { ...options, showCommentInfo: false });
			}

			if (messageHelper.isDeleted)
			{
				return new DeletedMessage(modelMessage, options);
			}

			if (messageHelper.isError)
			{
				return new ErrorMessage(modelMessage, options);
			}

			if (modelMessage.params?.componentId === MessageParams.ComponentId.CopilotMessage)
			{
				return new CopilotMessage(modelMessage, options);
			}

			if (modelMessage.params?.componentId === MessageParams.ComponentId.ConvertToCollabMessage)
			{
				return new SystemTextMessage(modelMessage, { ...options, showCommentInfo: false });
			}

			if (CheckInMessageFactory.checkSuitableForDisplay(modelMessage))
			{
				return CheckInMessageFactory.create(modelMessage, options);
			}

			if (messageHelper.isMediaGallery)
			{
				return new MediaGalleryMessage(modelMessage, options, files);
			}

			if (messageHelper.isFileGallery)
			{
				return new FileGalleryMessage(modelMessage, options, files);
			}

			if (CallMessageFactory.checkSuitableForDisplay(modelMessage))
			{
				return CallMessageFactory.create(modelMessage, options);
			}

			if (messageHelper.isVote && VoteMessageFactory.checkSuitableForDisplay(modelMessage))
			{
				return VoteMessageFactory.create(modelMessage, options);
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

			if (messageHelper.isText && !messageHelper.isCustom)
			{
				return new TextMessage(modelMessage, options);
			}

			return new UnsupportedMessage(modelMessage, options);
		}

		createMessageFromRecent()
		{
			const recentItem = serviceLocator.get('core').getStore().getters['recentModel/getById'](this.dialogId);
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

			const dialog = serviceLocator.get('core').getStore().getters['dialoguesModel/getById'](this.dialogId);

			const options = dialog
				? this.prepareSharedOptionsForMessages(dialog)
				: defaultOptions
			;

			const storedMessage = serviceLocator.get('core').getStore().getters['messagesModel/getById'](recentMessage.id);

			const modelMessage = ('id' in storedMessage)
				? storedMessage
				: defaultModelMessage
			;

			return this.createMessage(modelMessage, options);
		}

		/**
		 *
		 * @param {DialoguesModelState} dialog
		 * @return {CreateMessageOptions}
		 */
		prepareSharedOptionsForMessages(dialog)
		{
			/** @type {CreateMessageOptions} */
			const options = {
				dialogCode: this.dialogCode,
			};
			if (dialog.type === DialogType.user || dialog.type === DialogType.private)
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
		MessageUiConverter,
	};
});
