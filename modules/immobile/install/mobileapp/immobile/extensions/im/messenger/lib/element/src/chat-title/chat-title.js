/**
 * @module im/messenger/lib/element/chat-title
 */
jn.define('im/messenger/lib/element/chat-title', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Color } = require('tokens');
	const { Type } = require('type');
	const { Loc } = require('loc');

	const { Feature } = require('im/messenger/lib/feature');
	const { DeveloperSettings } = require('im/messenger/lib/dev/settings');
	const { Theme } = require('im/lib/theme');
	const {
		DialogType,
		BotType,
		UserType,
	} = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { MessengerParams } = require('im/messenger/lib/params');

	const ChatType = Object.freeze({
		user: 'user',
		chat: 'chat',
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
			this.dialogType = null;

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
			this.dialogType = dialog.type;

			this.description = ChatTitle.getChatDescriptionByDialogType(this.dialogType);

			if (this.dialogType === DialogType.comment)
			{
				const parentDialog = this.store.getters['dialoguesModel/getByChatId'](dialog.parentChatId);

				const parentDialogName = parentDialog?.name ?? '';

				this.description = Loc.getMessage('IMMOBILE_ELEMENT_CHAT_TITLE_COMMENT_DETAIL_TEXT')
					.replace('#CHANNEL_TITLE#', parentDialogName)
				;

				this.name = Loc.getMessage('IMMOBILE_ELEMENT_CHAT_TITLE_COMMENT');
			}
			else if (dialog.type && dialog.type === DialogType.copilot)
			{
				this.description = this.getCopilotRoleName();
			}

			this.createDialogNameColor(dialog);
		}

		/**
		 * @param {DialogType} dialogType
		 */
		static getChatDescriptionByDialogType(dialogType)
		{
			switch (dialogType)
			{
				case DialogType.copilot:
				{
					return Loc.getMessage('IMMOBILE_ELEMENT_CHAT_TITLE_ONLINE');
				}

				case DialogType.channel:
				{
					return Loc.getMessage('IMMOBILE_ELEMENT_CHAT_TITLE_CHANNEL_V2');
				}

				case DialogType.generalChannel:
				case DialogType.openChannel:
				{
					return Loc.getMessage('IMMOBILE_ELEMENT_CHAT_TITLE_OPEN_CHANNEL');
				}

				case DialogType.collab:
				{
					return Loc.getMessage('IMMOBILE_ELEMENT_CHAT_TITLE_COLLAB');
				}

				default:
				{
					return Loc.getMessage('IMMOBILE_ELEMENT_CHAT_TITLE_GROUP_MSGVER_1');
				}
			}
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
			else if (user.type === UserType.collaber)
			{
				this.description = Loc.getMessage('IMMOBILE_ELEMENT_CHAT_TITLE_USER_COLLABER');
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
			if (dialog.type === DialogType.collab)
			{
				this.nameColor = Theme.colors.base1;

				return;
			}

			if (dialog.extranet === true)
			{
				this.nameColor = Theme.colors.accentExtraBrown;

				return;
			}

			if (dialog.type === DialogType.support24Notifier)
			{
				this.nameColor = Theme.colors.accentMainLink;

				return;
			}

			if (dialog.type === DialogType.support24Question)
			{
				this.nameColor = Theme.colors.accentMainLink;
			}
		}

		/**
		 * @private
		 * @param {UsersModelState} user
		 */
		createUserNameColor(user)
		{
			if (user.type === UserType.collaber)
			{
				this.nameColor = Theme.colors.collabAccentPrimaryAlt;

				return;
			}

			if (user.extranet === true)
			{
				this.nameColor = Theme.colors.accentExtraBrown;

				return;
			}

			if (user.botData.type === BotType.support24)
			{
				this.nameColor = Theme.colors.accentMainLink;

				return;
			}

			if (user.network === true)
			{
				this.nameColor = Theme.colors.accentSoftElementGreen;

				return;
			}

			if (user.connector === true)
			{
				this.nameColor = Theme.colors.accentSoftElementGreen;

				return;
			}

			if (user.bot === true)
			{
				this.nameColor = Theme.colors.accentSoftElementViolet;
			}
		}

		/**
		 *
		 * @return {ChatTitleTileParams}
		 */
		getTitleParams(options = {})
		{
			if (this.type === ChatType.user)
			{
				return this.#getUserTitleParams(options);
			}

			return this.#getDialogTitleParams(options);
		}

		#getUserTitleParams(options = {})
		{
			const {
				useTextColor = true,
			} = options;

			const titleParams = {
				detailTextColor: AppTheme.colors.base3,
			};

			if (this.name)
			{
				titleParams.text = this.getTitle();
			}

			if (useTextColor)
			{
				titleParams.textColor = this.getTitleColor();
			}

			if (this.description)
			{
				titleParams.detailText = this.getDescription();
			}

			if (this.writingList.length > 0)
			{
				titleParams.detailText = this.buildWritingListText();
				titleParams.isWriting = true;
				titleParams.detailTextColor = Theme.colors.accentMainPrimaryalt;
			}

			return titleParams;
		}

		#getDialogTitleParams(options = {})
		{
			const {
				useTextColor = true,
			} = options;

			const titleParams = {
				detailTextColor: AppTheme.colors.base3,
			};

			if (this.name)
			{
				titleParams.text = this.getTitle();
			}

			if (useTextColor)
			{
				titleParams.textColor = this.getTitleColor();
			}

			if (this.description)
			{
				titleParams.detailText = this.getDescription();
			}

			if (this.userCounter)
			{
				if ([DialogType.openChannel, DialogType.channel, DialogType.generalChannel].includes(this.dialogType))
				{
					titleParams.detailText = Loc.getMessagePlural(
						'IMMOBILE_ELEMENT_CHAT_TITLE_SUBSCRIBER_COUNT',
						this.userCounter,
						{
							'#COUNT#': this.userCounter,
						},
					);
				}
				else
				{
					titleParams.detailText = Loc.getMessagePlural(
						'IMMOBILE_ELEMENT_CHAT_TITLE_USER_COUNT',
						this.userCounter,
						{
							'#COUNT#': this.userCounter,
						},
					);

					if (this.dialogType === DialogType.collab)
					{
						const guestCount = this.store.getters['dialoguesModel/collabModel/getGuestCountByDialogId'](this.dialogId);
						if (guestCount > 0)
						{
							const questText = Loc.getMessagePlural(
								'IMMOBILE_ELEMENT_CHAT_TITLE_COLLAB_GUEST_COUNT',
								guestCount,
								{
									'#COUNT#': guestCount,
								},
							);

							titleParams.detailText += ` [color=${Color.collabAccentPrimaryAlt.toHex()}]${questText}[/color]`;
						}
					}
				}
			}

			if (this.dialogType === DialogType.comment)
			{
				titleParams.detailText = this.description;
			}

			if (this.userCounter <= 2 && this.dialogType === DialogType.copilot)
			{
				titleParams.detailText = this.description;
			}

			if (this.writingList.length > 0)
			{
				titleParams.detailText = this.buildWritingListText();
				titleParams.isWriting = true;
				titleParams.detailTextColor = this.dialogType === DialogType.copilot
					? Theme.colors.accentMainCopilot
					: Theme.colors.accentMainPrimaryalt;
			}

			return titleParams;
		}

		/**
		 *
		 * @return {string|null}
		 */
		getTitle()
		{
			if (
				Feature.isDevelopmentEnvironment
				&& DeveloperSettings.getSettingValue('showDialogIds')
				&& this.dialogId
			)
			{
				return `[${this.dialogId}] ${this.name}`;
			}

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

			const currentUserId = serviceLocator.get('core').getUserId();
			this.writingList = dialogModel?.writingList.filter((user) => user.userId !== currentUserId);
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

		/**
		 * @desc get name copilot role
		 * @return {string}
		 * @private
		 */
		getCopilotRoleName()
		{
			const copilotMainRole = this.store.getters['dialoguesModel/copilotModel/getMainRoleByDialogId'](this.dialogId);
			if (!copilotMainRole || !Type.isStringFilled(copilotMainRole?.name))
			{
				return Loc.getMessage('IMMOBILE_ELEMENT_CHAT_TITLE_ONLINE');
			}

			return copilotMainRole?.name;
		}
	}

	module.exports = {
		ChatTitle,
	};
});
