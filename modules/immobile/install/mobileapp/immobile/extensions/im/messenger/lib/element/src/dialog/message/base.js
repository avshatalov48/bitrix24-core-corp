/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

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
		constructor(modelMessage = {}, options = {})
		{
			this.type = this.getType();

			this.id = '';
			this.username = '';
			this.avatarUrl = null;
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

			let likeList = [];
			if (modelMessage.params && modelMessage.params.REACTION && modelMessage.params.REACTION.like)
			{
				likeList = modelMessage.params.REACTION.like;
			}

			this
				.setId(modelMessage.id)
				.setTestId(modelMessage.id)
				.setUsername(modelMessage.authorId)
				.setAvatar(modelMessage.authorId)
				.setMe(modelMessage.authorId)
				.setTime(modelMessage.date)
				.setWasSent(!modelMessage.error)
				.setStatus(modelMessage)
				.setStatusText(modelMessage)
				.setLikes(likeList)
				.setShowUsername(options.showUsername)
				.setShowAvatar(options.showAvatar)
				.setFontColor(options.fontColor)
				.setIsBackgroundOn(options.isBackgroundOn)
				.setShowReaction(options.showReaction)
				.setCanBeQuoted(true)
				.setRoundedCorners(true)
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
				this.testId = 'DIALOG_MESSAGE_' + id.toString();

				return this;
			}

			return this;
		}

		setUsername(authorId)
		{
			const user = core.getStore().getters['usersModel/getUserById'](authorId);

			this.username = (user && user.name) ? user.name : '';

			return this;
		}

		setAvatar(authorId)
		{
			const user = core.getStore().getters['usersModel/getUserById'](authorId);

			this.avatarUrl = (user && user.avatar) ? user.avatar : null;

			return this;
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
			this.message = parser.decodeMessageFromText(text);
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

		setLikes(likeList)
		{
			if (!Type.isArray(likeList))
			{
				return this;
			}

			this.likeCount = likeList.length;
			this.meLiked = likeList.includes(MessengerParams.getUserId());

			return this;
		}

		setShowUsername(shouldShowUserName)
		{
			if (!Type.isBoolean(shouldShowUserName))
			{
				return this;
			}

			this.showUsername = shouldShowUserName;

			return this;
		}

		setShowAvatar(shouldShowAvatar)
		{
			if (!Type.isBoolean(shouldShowAvatar))
			{
				return this;
			}

			this.showAvatar = shouldShowAvatar;

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
			]

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
				MessageTextAlign.right
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

		setShowTail(showTail)
		{
			if (showTail)
			{
				this._enableTail();
			}
			else
			{
				this._disableTail();
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

		_enableTail()
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

		_disableTail()
		{
			delete this.style.leftTail;
			delete this.style.rightTail;
		}
	}

	module.exports = {
		Message,
		MessageAlign,
		MessageTextAlign,
	};
});
