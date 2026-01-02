/**
 * @module im/messenger/lib/element/chat-title
 */
jn.define('im/messenger/lib/element/chat-title', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Color } = require('tokens');
	const { Type } = require('type');
	const { Icon } = require('assets/icons');

	const { Feature } = require('im/messenger/lib/feature');
	const { DeveloperSettings } = require('im/messenger/lib/dev/settings');
	const { Theme } = require('im/lib/theme');
	const {
		DialogType,
		BotType,
		BotCode,
		UserType,
		UserInputAction,
	} = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const {
		DialogHelper,
		UserHelper,
	} = require('im/messenger/lib/helper');
	const { Loc } = require('im/messenger/loc');
	const { ChatTitleAssets } = require('im/messenger/assets/common');

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
		 * @param {ChatTitleOptions} options
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
			this.inputActions = [];
			this.dialogType = null;
			this.titleIcons = null;
			this.isCurrentUser = UserHelper.isCurrentUser(dialogId);

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

			this.setTitleIcons();

			if (!options.ignoreInputActions)
			{
				this.setInputActions();
			}
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
					return Loc.getMessage('IMMOBILE_MESSENGER_COMMON_CHAT_TITLE_GROUP_MSGVER_1');
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

		setTitleIcons()
		{
			if (!Feature.isTitleIconsInDialogHeaderAvailable)
			{
				return;
			}

			const dialogHelper = DialogHelper.createByDialogId(this.dialogId);
			const dialog = dialogHelper?.dialogModel;
			if (!dialogHelper || !dialog)
			{
				return;
			}

			if (dialogHelper.isMuted)
			{
				this.titleIcons = {
					right: {
						name: Icon.SMALL_NOTIFICATION_OFF.getIconName(),
						tintColor: AppTheme.colors.base3,
					},
				};
			}

			if (dialog.messagesAutoDeleteDelay)
			{
				this.titleIcons = {
					right: {
						name: Icon.SMALL_TIMER_DOT.getIconName(),
						tintColor: AppTheme.colors.base3,
					},
				};
			}
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
				this.nameColor = Theme.colors.accentExtraOrange;

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

				return;
			}

			if (dialog.containsCollaber)
			{
				this.nameColor = Theme.colors.collabAccentPrimaryAlt;
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
				this.nameColor = Theme.colors.accentExtraOrange;

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

			if (user?.botData?.code === BotCode.aiAssistant)
			{
				this.nameColor = Theme.colors.base1;
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

			if (this.titleIcons)
			{
				titleParams.titleIcons = this.titleIcons;
			}

			if (this.name)
			{
				titleParams.text = this.getTitle(options);
			}

			if (useTextColor)
			{
				titleParams.textColor = this.getTitleColor(options);
			}

			if (this.description)
			{
				titleParams.detailText = this.getDescription(options);
			}

			if (this.inputActions.length > 0)
			{
				titleParams.detailText = this.#buildInputActionTextPrivateChat();
				titleParams.detailLottie = this.#buildDetailLottie();
				titleParams.hasInputActions = true;
				titleParams.detailTextColor = Theme.colors.accentMainPrimaryalt;
			}

			if (this.isCurrentUser)
			{
				titleParams.detailLottie = null;
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

			if (this.titleIcons)
			{
				titleParams.titleIcons = this.titleIcons;
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

			if (this.inputActions.length > 0)
			{
				titleParams.detailText = this.buildInputActionTextGroupChat();
				titleParams.detailLottie = this.#buildDetailLottie();
				titleParams.hasInputActions = true;
				titleParams.detailTextColor = Theme.colors.accentMainPrimaryalt;
			}

			return titleParams;
		}

		/**
		 * @param {boolean} useNotes
		 * @return {string|null}
		 */
		getTitle({ useNotes = false } = {})
		{
			if (useNotes && this.isCurrentUser)
			{
				return Loc.getMessage('IMMOBILE_ELEMENT_CHAT_TITLE_NOTES');
			}

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

		/**
		 * @param {boolean} useNotes
		 * @returns {*|string}
		 */
		getTitleColor({ useNotes = false } = {})
		{
			if (useNotes && this.isCurrentUser)
			{
				return Color.base1.toHex();
			}

			return this.nameColor;
		}

		/**
		 * @param {boolean} useNotes
		 * @return {string|null}
		 */
		getDescription({ useNotes = false } = {})
		{
			if (useNotes && this.isCurrentUser)
			{
				return '';
			}

			return this.description;
		}

		/**
		 * @desc Get name writing user from model and set to array
		 * @void
		 * @private
		 */
		setInputActions()
		{
			const dialogModel = this.store.getters['dialoguesModel/getById'](this.dialogId);
			if (!dialogModel)
			{
				return;
			}

			this.inputActions = dialogModel.inputActions;
		}

		/**
		 * @return {string}
		 */
		buildInputActionTextGroupChat()
		{
			return Feature.isLottieInChatTitleAvailable
				? this.#buildInputActionTextGroupChat()
				: this.#buildInputActionTextGroupChatOldVersion();
		}

		/**
		 * @return {string}
		 */
		#buildInputActionTextGroupChatOldVersion()
		{
			const userCount = this.inputActions.length;
			const firstUser = this.inputActions[0];
			const secondUser = this.inputActions[1];

			if (userCount === 1)
			{
				return Loc.getMessage(
					'IMMOBILE_ELEMENT_CHAT_TITLE_WRITING_ONE',
					{
						'#USERNAME_1#': firstUser.userFirstName,
					},
				);
			}

			if (userCount === 2)
			{
				return Loc.getMessage(
					'IMMOBILE_ELEMENT_CHAT_TITLE_WRITING_TWO',
					{
						'#USERNAME_1#': firstUser.userFirstName,
						'#USERNAME_2#': secondUser.userFirstName,
					},
				);
			}

			if (userCount > 2)
			{
				return Loc.getMessage(
					'IMMOBILE_ELEMENT_CHAT_TITLE_WRITING_MORE',
					{
						'#USERNAME_1#': firstUser.userFirstName,
						'#USERS_COUNT#': userCount - 1,
					},
				);
			}

			return '';
		}

		/**
		 * @return {string}
		 */
		#buildInputActionTextGroupChat()
		{
			const userCount = this.inputActions.length;
			const firstUser = this.inputActions[0];
			const secondUser = this.inputActions[1];

			if (userCount === 1)
			{
				const { userFirstName } = firstUser;

				return userFirstName;
			}

			if (userCount === 2)
			{
				return `${firstUser.userFirstName}, ${secondUser.userFirstName}`;
			}

			if (userCount > 2)
			{
				return Loc.getMessage(
					'IMMOBILE_ELEMENT_CHAT_TITLE_WRITING_MORE_V2',
					{
						'#USERNAME_1#': firstUser.userFirstName,
						'#USERS_COUNT#': userCount - 1,
					},
				);
			}

			return '';
		}

		/**
		 * @return {string}
		 */
		#buildInputActionTextPrivateChat()
		{
			const inputActionsByUser = this.inputActions[0];
			let messages = {};

			if (Feature.isLottieInChatTitleAvailable)
			{
				messages = {
					[UserInputAction.writing]: 'IMMOBILE_ELEMENT_PRIVATE_CHAT_TITLE_WRITING_V1',
					[UserInputAction.recordingVoice]: 'IMMOBILE_ELEMENT_PRIVATE_CHAT_TITLE_RECORDING_VOICE',
					[UserInputAction.sendingFile]: 'IMMOBILE_ELEMENT_PRIVATE_CHAT_TITLE_SENDING_FILE',
				};
			}
			else
			{
				messages = {
					[UserInputAction.writing]: 'IMMOBILE_ELEMENT_PRIVATE_CHAT_TITLE_WRITING_V2',
					[UserInputAction.recordingVoice]: 'IMMOBILE_ELEMENT_PRIVATE_CHAT_TITLE_RECORDING_VOICE_V2',
					[UserInputAction.sendingFile]: 'IMMOBILE_ELEMENT_PRIVATE_CHAT_TITLE_SENDING_FILE_V2',
				};
			}

			const { type, statusMessageCode } = inputActionsByUser.action;

			if (!messages[type])
			{
				return '';
			}

			if (!Type.isNull(statusMessageCode))
			{
				return Loc.getMessage(statusMessageCode) || Loc.getMessage(messages[UserInputAction.writing]);
			}

			return Loc.getMessage(messages[type]);
		}

		/**
		 * @return {string}
		 */
		#buildDetailLottie()
		{
			if (!Feature.isLottieInChatTitleAvailable)
			{
				return null;
			}

			const userCount = this.inputActions.length;
			const firstUser = this.inputActions[0];

			const lottie = {
				[UserInputAction.writing]: ChatTitleAssets.getLottieUrl(UserInputAction.writing),
				[UserInputAction.recordingVoice]: ChatTitleAssets.getLottieUrl(UserInputAction.recordingVoice),
				[UserInputAction.sendingFile]: ChatTitleAssets.getLottieUrl(UserInputAction.sendingFile),
			};

			if (userCount === 1)
			{
				const { type } = firstUser.action;
				const lottieAction = lottie[type];

				if (lottieAction)
				{
					return { uri: lottieAction };
				}

				return null;
			}

			if (userCount > 1)
			{
				return {
					uri: ChatTitleAssets.getLottieUrl(UserInputAction.writing),
				};
			}

			return null;
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
