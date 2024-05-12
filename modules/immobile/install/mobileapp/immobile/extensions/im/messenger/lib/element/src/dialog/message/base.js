/**
 * @module im/messenger/lib/element/dialog/message/base
 */
jn.define('im/messenger/lib/element/dialog/message/base', (require, exports, module) => {
	const { Type } = require('type');
	const { Loc } = require('loc');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { OwnMessageStatus } = require('im/messenger/const');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { DateFormatter } = require('im/messenger/lib/date-formatter');
	const { parser } = require('im/messenger/lib/parser');
	const { defaultUserIcon } = require('im/messenger/assets/common');
	const { ColorUtils } = require('im/messenger/lib/utils');
	const { ReactionType } = require('im/messenger/const');
	const { Feature } = require('im/messenger/lib/feature');
	const { DeveloperSettings } = require('im/messenger/lib/dev/settings');

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
			this.username = '';
			this.avatarUrl = '';
			this.me = false;
			this.time = '';
			this.likeCount = 0;
			this.meLiked = false;
			this.read = true;
			this.quoteMessage = null;
			this.showReaction = true;
			this.canBeQuoted = true;
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

			/** @type {MessageRichLink || null} */
			this.richLink = null;

			this.showUsername = true;
			// TODO change user color for message
			this.userColor = '#428ae8';
			this.isAuthorBottomMessage = false;
			this.isAuthorTopMessage = false;

			this
				.setId(modelMessage.id)
				.setTestId(modelMessage.id)
				.setUsername(modelMessage.authorId)
				.setAvatar(modelMessage.authorId)
				.setUserColor(modelMessage.authorId)
				.setMe(modelMessage.authorId)
				.setTime(modelMessage.date)
				.setStatus(modelMessage)
				.setStatusText(modelMessage)
				.setForwardText(modelMessage)
				.setLikes(modelMessage.reactions)
				.setReactions(modelMessage.reactions)
				.setShowUsername(modelMessage, options.showUsername)
				.setShowAvatar(modelMessage, options.showAvatar)
				.setFontColor(options.fontColor)
				.setIsBackgroundOn(options.isBackgroundOn)
				.setShowReaction(options.showReaction)
				.setCanBeQuoted(options.canBeQuoted)
				.setRoundedCorners(true)
				.setMarginTop(options.marginTop)
				.setMarginBottom(options.marginBottom)
				.setRichLink(modelMessage)
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

		setUsername(authorId)
		{
			const user = serviceLocator.get('core').getStore().getters['usersModel/getById'](authorId);

			this.username = (user && user.name) ? user.name : '';

			return this;
		}

		setAvatar(authorId)
		{
			const user = serviceLocator.get('core').getStore().getters['usersModel/getById'](authorId);

			this.avatarUrl = (user && user.avatar) ? user.avatar : '';

			return this;
		}

		setUserColor(authorId)
		{
			const user = serviceLocator.get('core').getStore().getters['usersModel/getById'](authorId);

			this.userColor = (user && user.color) ? user.color : '#048bd0';

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

			if (attach && !attachWithOnlyRichLink)
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
				messageText += `\n\n[[b]ID:[/b] ${messageId}]`;
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

								if (userModel.avatar !== '')
								{
									result.imageUrl = userModel.avatar;

									return result;
								}

								result.defaultIconSvg = defaultUserIcon(
									userModel
										? userModel.color
										: colorUtils.getColorByNumber(userModel.id),
								);

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
		 * @param {MessagesModelState} messageModel
		 * @return {Message}
		 */
		setRichLink(messageModel)
		{
			const urlId = messageModel.richLinkId;

			if (!urlId)
			{
				return this;
			}

			/** @type {AttachConfig || undefined} */
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
					const isServerError = modelMessage.errorReason === 0 || modelMessage.errorReason === 500;
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

		setForwardText(modelMessage)
		{
			const { forward } = modelMessage;
			if (forward && forward.id)
			{
				if (forward.userId)
				{
					const authorId = forward.userId;
					const user = serviceLocator.get('core').getStore().getters['usersModel/getById'](authorId);
					if (user)
					{
						this.forwardText = `${Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_FORWARD')} ${user.name || user.lastName || user.firstName}`;
					}
				}
				else
				{
					this.forwardText = Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_FORWARD_SYSTEM');
				}
			}

			return this;
		}

		/**
		 * @desc Set load message text ( before progress )
		 */
		setLoadText()
		{
			if (!Type.isStringFilled(this.loadText) && this.status === OwnMessageStatus.sending)
			{
				this.loadText = Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_PROCESSING');
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
	}

	module.exports = {
		Message,
		MessageAlign,
		MessageTextAlign,
	};
});
