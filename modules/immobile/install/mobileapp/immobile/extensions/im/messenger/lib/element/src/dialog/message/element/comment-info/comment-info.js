/**
 * @module im/messenger/lib/element/dialog/message/element/comment-info/comment-info
 */
jn.define('im/messenger/lib/element/dialog/message/element/comment-info/comment-info', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');

	const { defaultUserIcon } = require('im/messenger/assets/common');
	const { Theme } = require('im/lib/theme');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { ColorUtils } = require('im/messenger/lib/utils');
	const { ChatAvatar } = require('im/messenger/lib/element/chat-avatar');

	/**
	 * @class CommentInfo
	 */
	class CommentInfo
	{
		/** @type {number} */
		#channelId;
		/** @type {?CommentInfoModelState} */
		#commentInfo;

		/**
		 * @param {number} messageId
		 * @param {number} channelId
		 * @returns {CommentInfo}
		 */
		static createByMessagesModel({ messageId, channelId })
		{
			const commentInfo = serviceLocator.get('core').getStore()
				.getters['commentModel/getByMessageId']?.(messageId)
			;

			return new this({ commentInfo, channelId });
		}

		/**
		 * @param {?CommentInfoModelState} commentInfo
		 * @param {number} channelId
		 */
		constructor({ commentInfo, channelId })
		{
			this.#commentInfo = commentInfo;
			this.#channelId = channelId;
		}

		toMessageFormat()
		{
			if (!this.#commentInfo)
			{
				return {
					title: this.#getDefaultTitle(),
					totalCounter: 0,
					showLoader: false,
				};
			}

			return {
				title: this.#getTitle(),
				totalCounter: this.#getTotalCounter(),
				unreadCounter: this.#getUnreadCounter(),
				users: this.#getUsers(),
				showLoader: this.#commentInfo.showLoader,
			};
		}

		#getTitle()
		{
			const totalCounter = this.#getTotalCounter();

			if (totalCounter === 0)
			{
				return this.#getDefaultTitle();
			}

			return Loc.getMessagePlural('IMMOBILE_ELEMENT_DIALOG_MESSAGE_COMMENT_COUNT', totalCounter, {
				'#COUNT#': totalCounter,
			});
		}

		#getDefaultTitle()
		{
			return Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_COMMENT');
		}

		/**
		 * @returns {null | {color: string, value: string}}
		 */
		#getUnreadCounter()
		{
			const unreadCounter = serviceLocator.get('core').getStore()
				.getters['commentModel/getCommentCounter']?.({
					channelId: this.#channelId,
					commentChatId: this.#commentInfo.chatId,
				})
			;

			if (unreadCounter === 0)
			{
				return null;
			}

			return {
				color: Theme.colors.accentMainSuccess,
				value: `+${unreadCounter}`,
			};
		}

		#getTotalCounter()
		{
			// remove first system message from count
			return this.#commentInfo.messageCount > 0
				? this.#commentInfo.messageCount - 1
				: 0
			;
		}

		/**
		 * @returns {null | Array<{imageUrl: string, defaultIconSvg: string}>}
		 */
		#getUsers()
		{
			const users = serviceLocator.get('core').getStore()
				.getters['usersModel/getByIdList'](this.#commentInfo.lastUserIds)
			;

			if (!Type.isArrayFilled(users))
			{
				return null;
			}

			const colorUtils = new ColorUtils();

			return users.map((user) => {
				const result = {};

				const chatAvatar = ChatAvatar.createFromDialogId(user.id);
				if (user.avatar !== '')
				{
					result.avatar = chatAvatar.getMessageCommentInfoAvatarProps();

					/** @deprecated */
					result.imageUrl = user.avatar;

					return result;
				}

				const color = Type.isStringFilled(chatAvatar.getColor())
					? chatAvatar.getColor()
					: colorUtils.getColorByNumber(user.id)
				;

				result.defaultIconSvg = defaultUserIcon(color);

				return result;
			});
		}
	}

	module.exports = { CommentInfo };
});
