/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/lib/element/chat-title
 */
jn.define('im/messenger/lib/element/chat-title', (require, exports, module) => {

	const { Type } = jn.require('type');
	const { Loc } = jn.require('loc');
	const { DialogHelper } = jn.require('im/messenger/lib/helper');
	const { MessengerParams } = require('im/messenger/lib/params');

	const DialogSpecialType = Object.freeze({
		group: 'chat',
		channel: 'open',
	});

	/**
	 * @class ChatTitle
	 */
	class ChatTitle
	{
		/**
		 *
		 * @param {number|string} dialogId
		 * @param {Object} options
		 * @param {boolean} options.showItsYou
		 * @returns {ChatTitle}
		 */
		static createFromDialogId(dialogId, options = {})
		{
			return new this(dialogId, options);
		}

		/**
		 *
		 * @param {number|string} dialogId
		 * @param {Object} options
		 * @param {boolean} options.showItsYou
		 * @returns {ChatTitle}
		 */
		constructor(dialogId, options = {})
		{
			this.name = null;
			this.description = null;

			if (DialogHelper.isDialogId(dialogId))
			{
				this.createDialogTitle(dialogId, options);
			}
			else
			{
				this.createUserTitle(dialogId, options);
			}
		}

		createDialogTitle(dialogId, options = {})
		{
			const dialog = MessengerStore.getters['dialoguesModel/getById'](dialogId);
			if (!dialog)
			{
				return;
			}

			this.name = dialog.name;

			//TODO: add special types like announcement, call etc.
			if (dialog.type && dialog.type === DialogSpecialType.channel)
			{
				this.description = Loc.getMessage('IMMOBILE_ELEMENT_CHAT_TITLE_CHANNEL');
			}
			else
			{
				this.description = Loc.getMessage('IMMOBILE_ELEMENT_CHAT_TITLE_GROUP');
			}
		}

		createUserTitle(userId, options = {})
		{
			const user = MessengerStore.getters['usersModel/getUserById'](userId);
			if (!user)
			{
				return;
			}

			this.name = user.name;
			if (options.showItsYou && userId === MessengerParams.getUserId())
			{
				this.name = this.name + ' (' + Loc.getMessage('IMMOBILE_ELEMENT_CHAT_TITLE_ITS_YOU') + ')';
			}

			if (Type.isStringFilled(user.work_position))
			{
				this.description = user.work_position;
			}
			else if(Type.isStringFilled(user.workPosition))
			{
				this.description = user.workPosition;
			}
			else if (user.extranet)
			{
				this.description = Loc.getMessage('IMMOBILE_ELEMENT_CHAT_TITLE_USER_EXTRANET');
			}
			else
			{
				this.description = Loc.getMessage('IMMOBILE_ELEMENT_CHAT_TITLE_EMPLOYEE');
			}
		}

		getTitleParams()
		{
			const titleParams = {};

			if (this.name)
			{
				titleParams.text = this.name;
			}

			if (this.description)
			{
				titleParams.detailText = this.description;
			}

			return titleParams;
		}

		/**
		 *
		 * @return {string|null}
		 */
		getTitle()
		{
			return this.name;
		}

		/**
		 *
		 * @return {string|null}
		 */
		getDescription()
		{
			return this.description;
		}
	}

	module.exports = {
		ChatTitle,
	};
});
