/**
 * @module im/messenger/lib/element/dialog/message/base
 */
jn.define('im/messenger/lib/element/dialog/message/base', (require, exports, module) => {
	const { Type } = require('type');
	const { Loc } = require('loc');
	const { core } = require('im/messenger/core');
	const { OwnMessageStatus } = require('im/messenger/const');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { DateFormatter } = require('im/messenger/lib/date-formatter');
	const { parser } = require('im/messenger/lib/parser');
	const { defaultUserIcon } = require('im/messenger/assets/common');
	const { ColorUtils } = require('im/messenger/lib/utils');
	const { ReactionType } = require('im/messenger/const');

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
			this.isBackgroundWide = false;
			this.style = {
				textAlign: MessageTextAlign.left,
				isBackgroundOn: true,
				roundedCorners: true,
			};
			this.reactions = [];
			/** @deprecated */
			this.ownReactions = []; //TODO delete after the new format is supported on iOS

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
				.setWasSent(!modelMessage.error)
				.setStatus(modelMessage)
				.setStatusText(modelMessage)
				.setLikes(modelMessage.reactions)
				.setReactions(modelMessage.reactions)
				.setShowUsername(modelMessage, options.showUsername)
				.setShowAvatar(modelMessage, options.showAvatar)
				.setFontColor(options.fontColor)
				.setIsBackgroundOn(options.isBackgroundOn)
				.setShowReaction(options.showReaction)
				.setCanBeQuoted(true)
				.setRoundedCorners(true)
				.setMarginTop(options.marginTop)
				.setMarginBottom(options.marginBottom)
			;
		}

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
			const user = core.getStore().getters['usersModel/getById'](authorId);

			this.username = (user && user.name) ? user.name : '';

			return this;
		}

		setAvatar(authorId)
		{
			const user = core.getStore().getters['usersModel/getById'](authorId);

			this.avatarUrl = (user && user.avatar) ? user.avatar : '';

			return this;
		}

		setUserColor(authorId)
		{
			const user = core.getStore().getters['usersModel/getById'](authorId);

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

		setWasSent(wasSent)
		{
			if (Type.isBoolean(wasSent))
			{
				this.wasSent = wasSent;
			}

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

		setMessage(text = '')
		{
			let messageText = text;

			// TODO: remove after native support for attachments
			const modelMessage = core.store.getters['messagesModel/getById'](this.id);
			const isMessageWithAttach = modelMessage
				&& modelMessage.params
				&& modelMessage.params.ATTACH
				&& modelMessage.params.ATTACH[0]
			;
			if (isMessageWithAttach)
			{
				if (Type.isStringFilled(text))
				{
					messageText += '\n\n';
				}

				const attach = modelMessage.params.ATTACH[0];
				if (Type.isStringFilled(attach.DESCRIPTION) && attach.DESCRIPTION !== 'SKIP_MESSAGE')
				{
					messageText += `${attach.DESCRIPTION}\n`;
				}

				const openAttachText = Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_ATTACH_SHOW');
				// link to avoid processing by the general rules for /mobile/ (open in full screen widget)
				const openAttachUrl = `${core.getHost()}/immobile/in-app/message-attach/${modelMessage.id}`;
				const attachIcon = String.fromCodePoint(128_206);
				messageText += `${attachIcon} [b][url=${openAttachUrl}]${openAttachText}[/url][/b]`;
			}

			const message = parser.decodeMessageFromText(messageText);
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
							.map((user) => {
								const userModel = core.getStore().getters['usersModel/getById'](user.id);

								const result = {
									isCurrentUser: user.id === MessengerParams.getUserId(),
								};

								if (user.avatar !== '')
								{
									result.imageUrl = user.avatar;

									return result;
								}

								result.defaultIconSvg = defaultUserIcon(
									userModel
										? userModel.color
										: colorUtils.getColorByNumber(user.id),
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

		setShowUsername(modelMessage, shouldShowUserName)
		{
			const isYourMessage = modelMessage.authorId === core.getUserId();
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
			const isYourMessage = modelMessage.authorId === core.getUserId();
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
			if (modelMessage.authorId !== core.getUserId())
			{
				return this;
			}

			if (modelMessage.sending)
			{
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
