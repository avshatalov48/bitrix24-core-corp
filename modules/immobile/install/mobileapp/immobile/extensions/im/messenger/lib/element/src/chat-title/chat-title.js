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
		 * @param {ChatTitleOptions} options
		 * @returns {ChatTitle}
		 */
		constructor(dialogId, options = {})
		{
			this.store = core.getStore();
			this.dialogId = dialogId;
			this.name = null;
			this.description = null;
			this.userCounter = 0;
			this.writingList = [];

			if (DialogHelper.isDialogId(dialogId))
			{
				this.type = ChatType.chat;
				this.createDialogTitle(options);
			}
			else
			{
				this.type = ChatType.user;
				this.createUserTitle(options);
			}

			this.setWritingList();
		}

		createDialogTitle(options = {})
		{
			const dialog = this.store.getters['dialoguesModel/getById'](this.dialogId);
			if (!dialog)
			{
				return;
			}

			this.name = dialog.name;
			this.userCounter = dialog.userCounter;

			// TODO: add special types like announcement, call etc.
			if (dialog.type && dialog.type === DialogSpecialType.channel)
			{
				this.description = Loc.getMessage('IMMOBILE_ELEMENT_CHAT_TITLE_CHANNEL');
			}
			else
			{
				this.description = Loc.getMessage('IMMOBILE_ELEMENT_CHAT_TITLE_GROUP');
			}
		}

		/**
		 * @private
		 * @param {ChatTitleOptions?} options
		 */
		createUserTitle(options = {})
		{
			const user = this.store.getters['usersModel/getUserById'](this.dialogId);
			if (!user)
			{
				return;
			}

			this.name = user.name;
			if (options.showItsYou && user.id === MessengerParams.getUserId())
			{
				this.name = `${this.name} (${Loc.getMessage('IMMOBILE_ELEMENT_CHAT_TITLE_ITS_YOU')})`;
			}

			if (Type.isStringFilled(user.work_position))
			{
				this.description = user.work_position;
			}
			else if (Type.isStringFilled(user.workPosition))
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

		/**
		 *
		 * @return {ChatTitleTileParams}
		 */
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
				titleParams.detailText = Loc.getMessagePlural(
					'IMMOBILE_ELEMENT_CHAT_TITLE_USER_COUNT',
					this.userCounter,
					{
						'#COUNT#': this.userCounter,
					},
				);
			}

			if (this.writingList.length > 0)
			{
				titleParams.detailText = this.buildWritingListText();
				titleParams.isWriting = true;
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

		/**
		 * @desc Get name writing user from model and set to array
		 * @void
		 * @private
		 */
		setWritingList()
		{
			const dialogModel = this.store.getters['dialoguesModel/getById'](this.dialogId);
			if (!dialogModel)
			{
				this.writingList = [];

				return;
			}

			this.writingList = dialogModel.writingList;
		}

		/**
		 * @desc Build text 'who writing'
		 * @return {string} text
		 * @private
		 */
		buildWritingListText()
		{
			let text = '';
			const countName = this.writingList.length;

			const firstUser = this.store.getters['usersModel/getUserById'](this.writingList[0].userId);
			if (!firstUser)
			{
				return text;
			}

			const firstUserName = firstUser.firstName ? firstUser.firstName : firstUser.lastName;
			if (countName === 1)
			{
				text = Loc.getMessage(
					'IMMOBILE_ELEMENT_CHAT_TITLE_WRITING_ONE',
					{
						'#USERNAME_1#': firstUserName,
					},
				);
			}
			else if (countName === 2)
			{
				const secondUser = this.store.getters['usersModel/getUserById'](this.writingList[1].userId);
				if (secondUser)
				{
					text = Loc.getMessage(
						'IMMOBILE_ELEMENT_CHAT_TITLE_WRITING_TWO',
						{
							'#USERNAME_1#': firstUserName,
							'#USERNAME_2#': secondUser.firstName ? secondUser.firstName : secondUser.lastName,
						},
					);
				}
				else
				{
					text = Loc.getMessage(
						'IMMOBILE_ELEMENT_CHAT_TITLE_WRITING_ONE',
						{
							'#USERNAME_1#': firstUserName,
						},
					);
				}
			}
			else
			{
				text = Loc.getMessage(
					'IMMOBILE_ELEMENT_CHAT_TITLE_WRITING_MORE',
					{
						'#USERNAME_1#': firstUserName,
						'#USERS_COUNT#': countName - 1,
					},
				);
			}

			return text;
		}
	}

	module.exports = {
		ChatTitle,
	};
});
