/**
 * @module im/messenger/lib/element/chat-title
 */
jn.define('im/messenger/lib/element/chat-title', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Type } = require('type');
	const { Loc } = require('loc');

	const {
		DialogType,
		BotType,
	} = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { MessengerParams } = require('im/messenger/lib/params');

	const ChatType = Object.freeze({
		user: 'user',
		chat: 'chat',
	});

	const DialogSpecialType = Object.freeze({
		group: 'chat',
		channel: 'open',
		copilot: 'copilot',
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
			this.core = serviceLocator.get('core');
			this.messengerStore = this.core.getMessengerStore();
			this.store = this.core.getStore();
			this.dialogId = dialogId;
			this.name = null;
			this.nameColor = AppTheme.colors.base1;
			this.description = null;
			this.userCounter = 0;
			this.writingList = [];
			this.dialogSpecialType = DialogSpecialType.group;

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

		/**
		 * @private
		 * @param {ChatTitleOptions?} options
		 */
		createDialogTitle(options = {})
		{
			const dialog = this.store.getters['dialoguesModel/getById'](this.dialogId);
			if (!dialog)
			{
				return;
			}

			this.name = dialog.name;
			this.userCounter = dialog.userCounter;
			this.dialogSpecialType = dialog.type;

			// TODO: add special types like announcement, call etc.
			if (dialog.type && dialog.type === DialogSpecialType.channel)
			{
				this.description = Loc.getMessage('IMMOBILE_ELEMENT_CHAT_TITLE_CHANNEL');
			}
			else if (dialog.type && dialog.type === DialogSpecialType.copilot)
			{
				this.description = Loc.getMessage('IMMOBILE_ELEMENT_CHAT_TITLE_ONLINE');
			}
			else
			{
				this.description = Loc.getMessage('IMMOBILE_ELEMENT_CHAT_TITLE_GROUP');
			}

			this.createDialogNameColor(dialog);
		}

		/**
		 * @private
		 * @param {ChatTitleOptions?} options
		 */
		createUserTitle(options = {})
		{
			let user = this.messengerStore.getters['usersModel/getById'](this.dialogId);
			if (!user)
			{
				user = this.store.getters['usersModel/getById'](this.dialogId);
			}

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

			this.createUserNameColor(user);
		}

		/**
		 * @private
		 * @param {DialoguesModelState} dialog
		 */
		createDialogNameColor(dialog)
		{
			if (dialog.extranet === true)
			{
				this.nameColor = AppTheme.colors.accentSoftElementOrange1;

				return;
			}

			if (dialog.type === DialogType.support24Notifier)
			{
				this.nameColor = AppTheme.colors.accentMainLinks;

				return;
			}

			if (dialog.type === DialogType.support24Question)
			{
				this.nameColor = AppTheme.colors.accentMainLinks;
			}
		}

		/**
		 * @private
		 * @param {UsersModelState} user
		 */
		createUserNameColor(user)
		{
			if (user.extranet === true)
			{
				this.nameColor = AppTheme.colors.accentSoftElementOrange1;

				return;
			}

			if (user.botData.type === BotType.support24)
			{
				this.nameColor = AppTheme.colors.accentMainLinks;

				return;
			}

			if (user.network === true)
			{
				this.nameColor = AppTheme.colors.accentSoftElementGreen1;

				return;
			}

			if (user.connector === true)
			{
				this.nameColor = AppTheme.colors.accentSoftElementGreen1;

				return;
			}

			if (user.bot === true)
			{
				this.nameColor = AppTheme.colors.accentExtraPurple;
			}
		}

		/**
		 *
		 * @return {ChatTitleTileParams}
		 */
		getTitleParams(options = {})
		{
			const {
				useTextColor = true,
			} = options;

			const titleParams = {
				detailTextColor: AppTheme.colors.base3,
			};

			if (this.name)
			{
				titleParams.text = this.name;
			}

			if (useTextColor)
			{
				titleParams.textColor = this.getTitleColor();
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

			if (this.userCounter <= 2 && this.dialogSpecialType === DialogSpecialType.copilot)
			{
				titleParams.detailText = this.description;
			}

			if (this.writingList.length > 0)
			{
				titleParams.detailText = this.buildWritingListText();
				titleParams.isWriting = true;
				titleParams.detailTextColor = this.dialogSpecialType === DialogSpecialType.copilot
					? AppTheme.colors.accentMainCopilot
					: AppTheme.colors.accentMainPrimaryalt;
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

		getTitleColor()
		{
			return this.nameColor;
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
		 * @return {string}
		 * @private
		 */
		buildWritingListText()
		{
			let text = '';
			const countName = this.writingList.length;

			if (!DialogHelper.isDialogId(this.dialogId))
			{
				return Loc.getMessage('IMMOBILE_ELEMENT_CHAT_TITLE_WRITING');
			}

			const firstUser = this.store.getters['usersModel/getById'](this.writingList[0].userId);
			if (!firstUser)
			{
				return text;
			}

			let firstUserName = firstUser.firstName ? firstUser.firstName : firstUser.lastName;
			if (firstUserName.length < 2) // TODO this if need remove after find bug empty name
			{
				const indexSpace = this.writingList[0].userName.indexOf(' ');
				if (indexSpace !== -1)
				{
					firstUserName = this.writingList[0].userName.slice(0, indexSpace);
				}
			}

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
				const secondUser = this.store.getters['usersModel/getById'](this.writingList[1].userId);
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
