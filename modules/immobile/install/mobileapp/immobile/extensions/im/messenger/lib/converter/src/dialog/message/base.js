/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/lib/converter/dialog/message/base
 */
jn.define('im/messenger/lib/converter/dialog/message/base', (require, exports, module) => {

	const { Type } = jn.require('type');
	const { MessengerParams } = jn.require('im/messenger/lib/params');

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
			this.read = true;
			this.quoteMessage = null;

			this.setId(modelMessage.id);

			this.setUsername(modelMessage.authorId);
			if (options.showUsername === false)
			{
				this.showUsername = false;
			}

			this.setAvatar(modelMessage.authorId);
			if (options.showAvatar === false)
			{
				this.showAvatar = false;
			}

			this.setMe(modelMessage.authorId);
			this.setTime(modelMessage.date);

			const likeList =
				(modelMessage.reactionCollection && modelMessage.reactionCollection.likeList)
					? modelMessage.reactionCollection.likeList
					: []
			;
			this.setLikeCount(likeList);

			this.setRead(modelMessage.isRead);
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
			}
		}

		setUsername(authorId)
		{
			const user = MessengerStore.getters['usersModel/getUserById'](authorId);

			this.username = (user && user.name) ? user.name : '';
		}

		setAvatar(authorId)
		{
			const user = MessengerStore.getters['usersModel/getUserById'](authorId);

			this.avatarUrl = (user && user.avatar) ? user.avatar : null;
		}

		setMe(authorId)
		{
			if (!Type.isNumber(authorId))
			{
				return;
			}

			this.me = authorId === MessengerParams.getUserId();
		}

		setRead(isRead)
		{
			if (!Type.isBoolean(isRead))
			{
				return;
			}

			this.read = isRead;
		}

		setTime(date)
		{
			if (!Type.isDate(date))
			{
				date = new Date(date);
			}

			if (Number.isNaN(date))
			{
				return '--:--';
			}

			const addZero = num => (num >= 0 && num <= 9) ? '0' + num : num;

			this.time = date.getHours() + ':' + addZero(date.getMinutes());
		}

		setLikeCount(likeList)
		{
			if (!Type.isArray(likeList))
			{
				return;
			}

			this.likeCount = likeList.length;
		}
	}

	module.exports = {
		Message,
	};
});
