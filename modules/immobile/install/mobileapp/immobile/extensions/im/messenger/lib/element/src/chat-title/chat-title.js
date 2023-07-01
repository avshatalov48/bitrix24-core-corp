/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/lib/element/chat-title
 */
jn.define('im/messenger/lib/element/chat-title', (require, exports, module) => {

	const { Type } = require('type');
	const { Loc } = require('loc');
	const { core } = require('im/messenger/core');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { MessengerParams } = require('im/messenger/lib/params');

	const ChatType = Object.freeze({
		user: 'user',
		chat: 'chat',
	});

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
			this.store = core.getStore();
			this.name = null;
			this.description = null;
			this.userCounter = 0;

			if (DialogHelper.isDialogId(dialogId))
			{
				this.type = ChatType.chat;
				this.createDialogTitle(dialogId, options);
			}
			else
			{
				this.type = ChatType.user;
				this.createUserTitle(dialogId, options);
			}
		}

		createDialogTitle(dialogId, options = {})
		{
			const dialog = this.store.getters['dialoguesModel/getById'](dialogId);
			if (!dialog)
			{
				return;
			}

			this.name = dialog.name;
			this.userCounter = dialog.userCounter;

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
			const user = this.store.getters['usersModel/getUserById'](userId);
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

			if (this.type === ChatType.user && this.description)
			{
				titleParams.detailText = this.description;
			}

			if (this.type === ChatType.chat && this.userCounter)
			{
				titleParams.detailText =
					Loc.getMessagePlural(
						'IMMOBILE_ELEMENT_CHAT_TITLE_USER_COUNT',
						this.userCounter,
						{
							'#COUNT#': this.userCounter
						}
					)
				;
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
