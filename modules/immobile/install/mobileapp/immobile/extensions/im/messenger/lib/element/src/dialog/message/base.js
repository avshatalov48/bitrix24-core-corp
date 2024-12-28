/**
 * @module im/messenger/lib/element/dialog/message/base
 */
jn.define('im/messenger/lib/element/dialog/message/base', (require, exports, module) => {
	const { Type } = require('type');
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { Color } = require('tokens');

	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { OwnMessageStatus, BotCode, DialogType, ErrorCode } = require('im/messenger/const');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { DateFormatter } = require('im/messenger/lib/date-formatter');
	const { parser } = require('im/messenger/lib/parser');
	const { defaultUserIcon } = require('im/messenger/assets/common');
	const { ColorUtils } = require('im/messenger/lib/utils');
	const {
		ReactionType,
		UserColor,
		UserType,
	} = require('im/messenger/const');
	const { Feature } = require('im/messenger/lib/feature');
	const { DeveloperSettings } = require('im/messenger/lib/dev/settings');
	const { Attach } = require('im/messenger/lib/element/dialog/message/element/attach/attach');
	const { Keyboard } = require('im/messenger/lib/element/dialog/message/element/keyboard/keyboard');
	const { CommentInfo } = require('im/messenger/lib/element/dialog/message/element/comment-info/comment-info');

	const { ChatTitle } = require('im/messenger/lib/element/chat-title');
	const { ChatAvatar } = require('im/messenger/lib/element/chat-avatar');

	const MessageAlign = Object.freeze({
		center: 'center',
	});

	const MessageTextAlign = Object.freeze({
		center: 'center',
		left: 'left',
		right: 'right',
	});

	/**
	 * @class Message
	 */
	class Message
	{
		/**
		 * @param {MessagesModelState} modelMessage
		 * @param {CreateMessageOptions} options
		 */
		constructor(modelMessage = {}, options = {})
		{
			this.type = this.getType();

			this.id = '';
			this.title = {};
			this.username = '';
			/** @deprecated use to this.avatar {AvatarDetail} */
			this.avatarUrl = '';
			this.avatar = null;
			this.me = false;
			this.time = '';
			this.likeCount = 0;
			this.meLiked = false;
			this.read = true;
			this.quoteMessage = null;
			this.showReaction = true;
			this.canBeQuoted = true;
			this.canBeChecked = true;
			this.align = null;
			this.statusText = '';
			this.forwardText = '';
			this.loadText = '';
			this.isBackgroundWide = false;
			this.style = {
				textAlign: MessageTextAlign.left,
				isBackgroundOn: true,
				roundedCorners: true,
			};
			this.reactions = [];
			/** @deprecated */
			this.ownReactions = []; // TODO delete after the new format is supported on iOS
			this.showAvatarsInReaction = true;

			/** @type {MessageRichLink || null} */
			this.richLink = null;

			this.attach = [];
			this.keyboard = [];

			this.showUsername = true;
			// TODO change user color for message
			/** @deprecated use to this.avatar {AvatarDetail} */
			this.userColor = UserColor.default;
			this.isAuthorBottomMessage = false;
			this.isAuthorTopMessage = false;

			this.commentInfo = null;

			this
				.setId(modelMessage.id)
				.setTestId(modelMessage.id)
				.setTitle(modelMessage)
				.setUsername(modelMessage.authorId)
				.setAvatar(modelMessage.authorId, modelMessage.chatId, modelMessage.id)
				.setUserColor(modelMessage.authorId)
				.setMe(modelMessage.authorId)
				.setTime(modelMessage.date)
				.setStatus(modelMessage)
				.setStatusText(modelMessage)
				.setForwardText(modelMessage)
				.setLikes(modelMessage.reactions)
				.setReactions(modelMessage.reactions)
				.setShowAvatarsInReaction(String(modelMessage.id), options)
				.setShowUsername(modelMessage, options.showUsername)
				.setShowAvatar(modelMessage, options.showAvatar)
				.setFontColor(options.fontColor)
				.setIsBackgroundOn(options.isBackgroundOn)
				.setShowReaction(options.showReaction)
				.setCanBeQuoted(options.canBeQuoted)
				.setCanBeChecked(options.canBeChecked)
				.setRoundedCorners(true)
				.setMarginTop(options.marginTop)
				.setMarginBottom(options.marginBottom)
				.setRichLink(modelMessage)
				.setAttach(modelMessage)
				.setKeyboard(modelMessage)
				.setCommentInfo(modelMessage, Boolean(options.showCommentInfo))
			;
		}

		/**
		 * @abstract
		 * @return {string}
		 */
		getType()
		{
			throw new Error('Message: getType() must be override in subclass.');
		}

		/**
		 * @return {MessagesModelState}
		 */
		getModelMessage()
		{
			return serviceLocator.get('core').getStore().getters['messagesModel/getById'](this.id);
		}

		setId(id)
		{
			if (
				!Type.isUndefined(id)
				&& (
					Type.isNumber(id)
					|| Type.isString(id)
				)
			)
			{
				this.id = id.toString();

				return this;
			}

			return this;
		}

		setTestId(id)
		{
			if (
				!Type.isUndefined(id)
				&& (
					Type.isNumber(id)
					|| Type.isString(id)
				)
			)
			{
				this.testId = `DIALOG_MESSAGE_${id.toString()}`;

				return this;
			}

			return this;
		}

		setTitle(modelMessage)
		{
			const authorId = modelMessage.authorId;
			const user = serviceLocator.get('core').getStore().getters['usersModel/getById'](authorId);
			if (!user)
			{
				return this;
			}

			const username = user.name ?? '';

			this.title = {
				text: username,
			};

			if (user.type === UserType.collaber)
			{
				this.title.color = Color.collabAccentPrimaryAlt.toHex();
			}

			return this;
		}

		/**
		 * @deprecated use setTitle instead
		 * @param authorId
		 * @return {Message}
		 */
		setUsername(authorId)
		{
			const user = serviceLocator.get('core').getStore().getters['usersModel/getById'](authorId);

			this.username = (user && user.name) ? user.name : '';

			return this;
		}

		setAvatar(authorId)
		{
			const user = serviceLocator.get('core').getStore().getters['usersModel/getById'](authorId);
			this.avatarUrl = user?.avatar ?? '';
			this.setAvatarDetail(user);

			return this;
		}

		/**
		 * @param {UsersModelState|null} user
		 * @void
		 */
		setAvatarDetail(user)
		{
			if (Type.isNil(user))
			{
				this.avatar = null;
			}
			else
			{
				this.avatar = ChatAvatar.createFromDialogId(user.id).getMessageAvatarProps();
			}
		}

		/**
		 * @deprecated use to AvatarDetail
		 */
		setUserColor(authorId)
		{
			const user = serviceLocator.get('core').getStore().getters['usersModel/getById'](authorId);

			this.userColor = user?.color ?? UserColor.default;

			return this;
		}

		/**
		 * @desc set data uri avatar
		 * @rules :
		 * showAvatar = false and avatarUrl = null - don't show avatar, don't show space
		 * showAvatar = false and avatarUrl = "" | "http://" - don't show avatar, add space
		 * showAvatar = true and avatarUrl = "" | "http://" - show avatar, add space
		 */
		setAvatarUri(value)
		{
			this.avatarUrl = value;
			if (Type.isObject(this.avatar))
			{
				this.avatar.uri = value;
			}
		}

		setMe(authorId)
		{
			if (!Type.isNumber(authorId))
			{
				return this;
			}

			this.me = authorId === MessengerParams.getUserId();

			return this;
		}

		setRead(isRead)
		{
			if (!Type.isBoolean(isRead))
			{
				return this;
			}

			this.read = isRead;

			return this;
		}

		setMessage(text = '', options = {})
		{
			let messageText = text;

			// TODO: remove after native support for attachments
			const modelMessage = serviceLocator.get('core').getStore().getters['messagesModel/getById'](this.id);

			const attach = modelMessage?.params?.ATTACH ? modelMessage.params.ATTACH[0] : null;

			const attachWithOnlyRichLink = Boolean(
				attach?.BLOCKS.length === 1
				&& attach.BLOCKS[0].RICH_LINK,
			);

			if (attach && !attachWithOnlyRichLink && !Feature.isMessageAttachSupported)
			{
				if (Type.isStringFilled(text))
				{
					messageText += '\n\n';
				}

				if (Type.isStringFilled(attach.DESCRIPTION) && attach.DESCRIPTION !== 'SKIP_MESSAGE')
				{
					messageText += `${attach.DESCRIPTION}\n`;
				}

				const openAttachText = Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_ATTACH_SHOW');
				// link to avoid processing by the general rules for /mobile/ (open in full screen widget)
				const openAttachUrl = `${serviceLocator.get('core').getHost()}/immobile/in-app/message-attach/${modelMessage.id}`;
				const attachIcon = String.fromCodePoint(128_206);
				messageText += `${attachIcon} [b][url=${openAttachUrl}]${openAttachText}[/url][/b]`;
			}

			if (
				Feature.isDevelopmentEnvironment
				&& DeveloperSettings.getSettingValue('showMessageId')
				&& modelMessage.id
			)
			{
				const messageId = modelMessage.id || modelMessage.templateId;
				const previousId = modelMessage.previousId;
				const nextId = modelMessage.nextId;
				messageText += `\n\n[[b]previousId:[/b] ${previousId}]`;
				messageText += `\n[[b]id:[/b] ${messageId}]`;
				messageText += `\n[[b]nextId:[/b] ${nextId}]`;
			}

			const message = parser.decodeMessageFromText(messageText, options);
			if (Type.isArrayFilled(message))
			{
				this.message = message;
			}
		}

		setTime(date)
		{
			if (!Type.isDate(date))
			{
				return this;
			}

			if (Number.isNaN(date))
			{
				this.time = '--:--';

				return this;
			}

			this.time = DateFormatter.getShortTime(date);

			return this;
		}

		/**
		 *
		 * @param {ReactionsModelState} reactions
		 * @return {Message}
		 */
		setLikes(reactions)
		{
			if (!Type.isPlainObject(reactions))
			{
				return this;
			}

			this.likeCount = Object.values(reactions.reactionCounters)
				.reduce((currentSum, currentNumber) => {
					return currentSum + currentNumber;
				}, 0)
			;

			this.meLiked = reactions.ownReactions.size > 0;

			return this;
		}

		/**
		 *
		 * @param {ReactionsModelState} reactionsList
		 */
		setReactions(reactionsList)
		{
			const colorUtils = new ColorUtils();
			if (!reactionsList)
			{
				this.ownReactions = [];
				this.reactions = [];

				return this;
			}

			this.ownReactions = [...reactionsList.ownReactions]; // TODO delete after the new format is supported on iOS

			const reactions = [];
			Object.values(ReactionType)
				/** @type {ReactionType} */
				.forEach((reactionType) => {
					if (!reactionsList.reactionCounters[reactionType])
					{
						return;
					}

					const reaction = {
						id: reactionType,
						testId: `REACTION_${reactionType.toUpperCase()}`,
						counter: reactionsList.reactionCounters[reactionType],
						meLiked: reactionsList.ownReactions.has(reactionType),
					};

					if (reactionsList.reactionUsers.has(reactionType))
					{
						reaction.users = reactionsList.reactionUsers
							.get(reactionType)
							.map((userId) => {
								const userModel = serviceLocator.get('core').getStore().getters['usersModel/getById'](userId);

								const result = {
									isCurrentUser: userId === MessengerParams.getUserId(),
								};

								if (!userModel)
								{
									return result;
								}

								const chatAvatar = ChatAvatar.createFromDialogId(userModel.id);
								if (userModel.avatar !== '')
								{
									result.avatar = chatAvatar.getReactionAvatarProps();

									/** @deprecated */
									result.imageUrl = userModel.avatar;

									return result;
								}

								const color = Type.isStringFilled(chatAvatar.getColor())
									? chatAvatar.getColor()
									: colorUtils.getColorByNumber(userModel.id)
								;

								result.defaultIconSvg = defaultUserIcon(color);

								return result;
							})
						;
					}

					reactions.push(reaction);
				})
			;

			this.reactions = reactions;

			return this;
		}

		/**
		 *
		 * @param {string} messageId
		 * @param {CreateMessageOptions} options
		 * @return {Message}
		 */
		setShowAvatarsInReaction(messageId, options)
		{
			if (Type.isBoolean(options.showAvatarsInReaction))
			{
				this.showAvatarsInReaction = options.showAvatarsInReaction;
			}

			if (options.initialPostMessageId === messageId)
			{
				this.showAvatarsInReaction = false;
			}

			return this;
		}

		/**
		 * @param {MessagesModelState} modelMessage
		 * @param {boolean} showCommentInfo
		 */
		setCommentInfo(modelMessage, showCommentInfo)
		{
			if (!showCommentInfo)
			{
				return this;
			}

			this.commentInfo = CommentInfo.createByMessagesModel({
				messageId: modelMessage.id,
				channelId: modelMessage.chatId,
			}).toMessageFormat();

			return this;
		}

		/**
		 * @deprecated
		 * @param {MessagesModelState} messageModel
		 * @return {Message}
		 */
		setRichLink(messageModel)
		{
			if (Feature.isMessageAttachSupported)
			{
				return this;
			}

			const urlId = messageModel.richLinkId;

			if (!urlId)
			{
				return this;
			}

			const attach = messageModel.attach.find((attachConfig) => {
				return Number(attachConfig.id) === urlId;
			});

			if (!attach)
			{
				return this;
			}

			/** @type {AttachRichItem || null} */
			let richLink = null;
			const blockWithRich = attach.blocks.find((attachBlock) => attachBlock.richLink);

			if (blockWithRich?.richLink.length > 0)
			{
				richLink = blockWithRich.richLink[0];
			}

			if (richLink)
			{
				let previewUrl = richLink.preview ?? null;

				if (Type.isString(previewUrl) && !previewUrl.startsWith('http'))
				{
					previewUrl = currentDomain + previewUrl;
				}

				this.richLink = {
					link: richLink.link ?? '',
					description: richLink.desc ?? '',
					name: richLink.name ?? '',
					attachId: attach.id,
					previewUrl,
					previewSize: {
						height: richLink?.previewSize?.height ?? 0,
						width: richLink?.previewSize?.width ?? 0,
					},
				};
			}

			return this;
		}

		setAttach(modelMessage)
		{
			if (!Feature.isMessageAttachSupported)
			{
				return this;
			}

			if (Type.isArrayFilled(modelMessage.attach))
			{
				this.attach = Attach
					.createByMessagesModelAttach(modelMessage.attach)
					.toMessageFormat()
				;
			}

			return this;
		}

		setKeyboard(modelMessage)
		{
			if (!Feature.isMessageKeyboardSupported)
			{
				return this;
			}

			if (Type.isArrayFilled(modelMessage.keyboard))
			{
				this.keyboard = Keyboard
					.createByMessagesModelKeyboard(modelMessage.keyboard)
					.toMessageFormat()
				;
			}

			return this;
		}

		setShowUsername(modelMessage, shouldShowUserName)
		{
			const isYourMessage = modelMessage.authorId === serviceLocator.get('core').getUserId();
			if (isYourMessage)
			{
				this.showUsername = false;
			}

			if (Type.isBoolean(shouldShowUserName))
			{
				this.showUsername = shouldShowUserName;

				return this;
			}

			return this;
		}

		setShowAvatar(modelMessage, shouldShowAvatar)
		{
			const isYourMessage = modelMessage.authorId === serviceLocator.get('core').getUserId();
			if (isYourMessage)
			{
				this.showAvatar = false;
			}

			if (Type.isBoolean(shouldShowAvatar))
			{
				this.showAvatar = shouldShowAvatar;

				return this;
			}

			return this;
		}

		setShowAvatarForce(shouldShowAvatar)
		{
			if (Type.isBoolean(shouldShowAvatar))
			{
				this.showAvatar = shouldShowAvatar;

				return this;
			}

			return this;
		}

		setShowReaction(shouldShowReaction)
		{
			if (!Type.isBoolean(shouldShowReaction))
			{
				return this;
			}

			this.showReaction = shouldShowReaction;

			return this;
		}

		setFontColor(color)
		{
			if (!Type.isStringFilled(color))
			{
				return this;
			}

			this.style.fontColor = color;

			return this;
		}

		setIsBackgroundOn(isBackgroundOn)
		{
			if (!Type.isBoolean(isBackgroundOn))
			{
				return this;
			}

			this.style.isBackgroundOn = isBackgroundOn;

			return this;
		}

		setBackgroundColor(color)
		{
			if (!Type.isString(color))
			{
				return this;
			}

			this.style.backgroundColor = color;

			return this;
		}

		setCanBeQuoted(canBeQuoted)
		{
			if (!Type.isBoolean(canBeQuoted))
			{
				return this;
			}

			this.canBeQuoted = canBeQuoted;

			return this;
		}

		setCanBeChecked(canBeChecked)
		{
			if (this.status === OwnMessageStatus.error)
			{
				this.canBeChecked = false;

				return this;
			}

			if (!Type.isBoolean(canBeChecked))
			{
				return this;
			}

			this.canBeChecked = canBeChecked;

			return this;
		}

		setMessageAlign(align)
		{
			const availableAlign = [
				MessageAlign.center,
			];

			if (availableAlign.includes(align))
			{
				this.align = align;
			}

			return this;
		}

		setTextAlign(align)
		{
			const availableTextAlign = [
				MessageTextAlign.center,
				MessageTextAlign.left,
				MessageTextAlign.right,
			];

			if (availableTextAlign.includes(align))
			{
				this.style.textAlign = align;
			}

			return this;
		}

		setIsBackgroundWide(isWide)
		{
			if (Type.isBoolean(isWide))
			{
				this.style.isBackgroundWide = isWide;
			}

			return this;
		}

		setRoundedCorners(shouldRoundCorners)
		{
			if (Type.isBoolean(shouldRoundCorners))
			{
				this.style.roundedCorners = shouldRoundCorners;
			}

			return this;
		}

		setMarginTop(px = 4)
		{
			if (Type.isNumber(px))
			{
				this.style.marginTop = px;
			}

			return this;
		}

		setMarginBottom(px = 4)
		{
			if (Type.isNumber(px))
			{
				this.style.marginBottom = px;
			}

			return this;
		}

		setShowTail(showTail)
		{
			if (showTail)
			{
				this.enableTail();
			}
			else
			{
				this.disableTail();
			}

			return this;
		}

		setStatus(modelMessage)
		{
			if (modelMessage.authorId !== serviceLocator.get('core').getUserId())
			{
				return this;
			}

			if (modelMessage.sending)
			{
				if (Type.isBoolean(modelMessage.error) && modelMessage.error && modelMessage.sending)
				{
					const dateSend = Type.isDate(modelMessage.date) ? modelMessage.date : new Date();
					const dateThreeDayAgo = new Date();
					dateThreeDayAgo.setDate(dateThreeDayAgo.getDate() - 3);

					const isWaitExpired = dateSend.getTime() < dateThreeDayAgo.getTime();
					const isServerError = modelMessage.errorReason === 0
						|| modelMessage.errorReason === ErrorCode.INTERNAL_SERVER_ERROR
					;
					this.status = (isWaitExpired || isServerError) ? OwnMessageStatus.error : OwnMessageStatus.sending;

					return this;
				}

				this.status = OwnMessageStatus.sending;
			}
			else if (modelMessage.viewedByOthers)
			{
				this.status = OwnMessageStatus.viewed;
			}
			else
			{
				this.status = OwnMessageStatus.sent;
			}

			return this;
		}

		setStatusText(modelMessage)
		{
			if (!modelMessage.params || !modelMessage.params.IS_EDITED)
			{
				return this;
			}

			if (modelMessage.params.IS_EDITED === 'Y')
			{
				this.statusText = Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_EDITED');
			}

			return this;
		}

		/**
		 *
		 * @param {MessagesModelState} modelMessage
		 * @return {Message}
		 */
		setForwardText(modelMessage)
		{
			const { forward } = modelMessage;

			if (!forward || !forward.id)
			{
				return this;
			}

			const authorId = forward.userId;
			const user = serviceLocator.get('core').getStore().getters['usersModel/getById'](authorId);

			if (!forward.userId || !user)
			{
				// forward system message
				this.forwardText = Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_FORWARD_SYSTEM');

				return this;
			}

			const userName = user.name || user.lastName || user.firstName;

			if ([DialogType.openChannel, DialogType.generalChannel].includes(forward.chatType))
			{
				this.forwardText = Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_FORWARD_CHANNEL')
					.replace('#USER_NAME#', userName)
					.replace('#CHANNEL_NAME#', forward.chatTitle)
				;

				return this;
			}

			if (forward.chatType === DialogType.channel)
			{
				const channelDescription = ChatTitle.getChatDescriptionByDialogType(forward.chatType);
				this.forwardText = Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_FORWARD_CHANNEL')
					.replace('#USER_NAME#', userName)
					.replace('#CHANNEL_NAME#', channelDescription)
				;

				return this;
			}

			this.forwardText = Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_FORWARD_MSGVER_1')
				.replace('#USER_NAME#', userName)
			;

			return this;
		}

		/**
		 * @desc Set load message text ( before progress )
		 */
		setLoadText()
		{
			if (!Type.isStringFilled(this.loadText) && this.status === OwnMessageStatus.sending)
			{
				this.loadText = Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_PROCESSING_MSGVER_1');
			}
		}

		/**
		 * @private
		 */
		enableTail()
		{
			if (this.me)
			{
				this.style.rightTail = true;
			}
			else
			{
				this.style.leftTail = true;
			}
		}

		/**
		 * @private
		 */
		disableTail()
		{
			delete this.style.leftTail;
			delete this.style.rightTail;
		}

		setAuthorBottomMessage(value)
		{
			this.isAuthorBottomMessage = value;
		}

		setAuthorTopMessage(value)
		{
			this.isAuthorTopMessage = value;
		}

		setUserNameColor(authorId)
		{
			this.style.userNameColor = AppTheme.colors.chatMyPrimary1;

			const user = serviceLocator.get('core').getStore().getters['usersModel/getById'](authorId);
			const isCopilot = user.bot && user.botData?.code === BotCode.copilot;
			if (isCopilot)
			{
				this.style.userNameColor = AppTheme.colors.accentMainCopilot;
			}
		}
	}

	module.exports = {
		Message,
		MessageAlign,
		MessageTextAlign,
	};
});
