/**
 * @module im/messenger/lib/element/chat-avatar
 */
jn.define('im/messenger/lib/element/chat-avatar', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Type } = require('type');
	const { Icon } = require('assets/icons');
	const { Typography, Color } = require('tokens');
	const { Theme } = require('im/lib/theme');
	const { merge } = require('utils/object');
	const { withCurrentDomain } = require('utils/url');
	const { getFirstLetters } = require('layout/ui/user/empty-avatar');

	const { Feature } = require('im/messenger/lib/feature');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { DialogHelper, UserHelper } = require('im/messenger/lib/helper');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { DialogType, UserType, BotCode } = require('im/messenger/const');
	const { defaultGroupChatAvatar } = require('im/messenger/assets/common');
	const { AvatarShape } = require('ui-system/blocks/avatar');
	const { ColorUtils } = require('im/messenger/lib/utils');

	const ASSET_PATH = '/bitrix/mobileapp/immobile/extensions/im/messenger/assets/common/';

	const AvatarDetailFields = Object.freeze({
		accentType: {
			orange: 'orange',
			green: 'green',
			blue: 'blue',
			purple: 'purple',
		},
		placeholderType: {
			auto: 'auto',
			letters: 'letters',
			svg: 'svg',
			none: 'none',
		},
	});

	const themeId = AppTheme.id;
	const DefaultUserNamedIcon = {
		[UserType.collaber]: `collab_user_${themeId}`,
		[UserType.extranet]: `extranet_user_${themeId}`,
		[UserType.user]: `default_user_${themeId}`,
	};

	/**
	 * @class ChatAvatar
	 */
	class ChatAvatar
	{
		/** @type {ColorUtils} */
		static #colorUtils;
		#avatar;

		static get colorUtils()
		{
			this.#colorUtils ??= new ColorUtils();

			return this.#colorUtils;
		}

		/**
		 *
		 * @param {string || number} dialogId
		 * @param {object} options
		 * @return {ChatAvatar}
		 */
		static createFromDialogId(dialogId, options = {})
		{
			return new this(dialogId, options);
		}

		constructor(dialogId, options = {})
		{
			this.options = options;
			this.store = serviceLocator.get('core').getStore();
			this.messengerStore = serviceLocator.get('core').getMessengerStore();
			this.#avatar = null;
			this.color = null;
			this.title = null;
			this.isSuperEllipseIcon = false;
			this.type = null;
			this.dialogId = dialogId;
			this.extranet = false;
			this.isCurrentUser = UserHelper.isCurrentUser(dialogId);

			if (DialogHelper.isDialogId(dialogId))
			{
				this.createDialogAvatar(dialogId);
			}
			else
			{
				this.createUserAvatar(dialogId);
			}
		}

		static getImagePath()
		{
			return `${currentDomain}/bitrix/mobileapp/immobile/extensions/im/messenger/lib/element/chat-avatar/src/images/`;
		}

		/**
		 * @private
		 * @param uri
		 */
		set avatar(uri)
		{
			this.#avatar = withCurrentDomain(uri);
		}

		/**
		 * @private
		 * @return {string|null}
		 */
		get avatar()
		{
			return this.#avatar;
		}

		createDialogAvatar(dialogId)
		{
			const dialogModel = this.getDialogById(dialogId);
			if (!dialogModel)
			{
				return;
			}

			this.color = dialogModel.color;
			this.avatar = Type.isStringFilled(dialogModel.avatar) ? dialogModel.avatar : null;
			this.title = dialogModel.name;
			this.type = dialogModel.type;
			this.extranet = dialogModel.extranet;

			if (this.extranet)
			{
				this.color = Theme.colors.accentExtraOrange;
			}

			if (this.type === DialogType.collab)
			{
				this.color = Theme.colors.collabAccentPrimary;
			}

			if ([DialogType.generalChannel, DialogType.openChannel, DialogType.channel].includes(this.type))
			{
				this.isSuperEllipseIcon = true;
			}

			if (dialogModel.chatId === MessengerParams.getGeneralChatId())
			{
				this.avatar = `${ChatAvatar.getImagePath()}avatar_general_chat.png`;

				return;
			}

			if (this.type === DialogType.generalChannel)
			{
				this.avatar = `${ChatAvatar.getImagePath()}avatar_general_channel.png`;

				return;
			}

			if (dialogModel.entityType === 'SUPPORT24_QUESTION')
			{
				this.avatar = `${ChatAvatar.getImagePath()}avatar_support_24.png`;

				return;
			}

			if (this.type === DialogType.copilot)
			{
				this.avatar = this.getCopilotMainRoleAvatar(dialogModel.dialogId) || `${ChatAvatar.getImagePath()}avatar_copilot_assistant.png`;
			}
		}

		createUserAvatar(userId)
		{
			const user = this.getUserById(userId);
			if (!user)
			{
				return;
			}

			if (this.isUser(userId) && !user.lastActivityDate && !user.avatar)
			{
				this.avatar = `${ChatAvatar.getImagePath()}avatar_wait_air.png`;
				this.color = user.color;

				return;
			}

			this.avatar = Type.isStringFilled(user.avatar) ? user.avatar : null;
			this.color = user.color;
			if (user.type === UserType.collaber)
			{
				this.color = Theme.colors.collabAccentPrimary;
			}

			if (user.type === UserType.extranet)
			{
				this.color = Theme.colors.accentMainWarning;
			}

			this.title = user.name;
			this.type = DialogType.private;
		}

		/**
		 * @deprecated - use to AvatarDetail
		 * @return {ChatAvatarTitleParams}
		 */
		getTitleParams()
		{
			if (this.type === DialogType.comment)
			{
				return {};
			}

			const titleParams = {
				useLetterImage: true,
				isSuperEllipseIcon: this.isSuperEllipseIcon,
			};

			if (this.avatar)
			{
				titleParams.imageUrl = this.avatar;
			}

			if (this.color && (this.avatar === '' || this.avatar === null))
			{
				titleParams.imageColor = this.color;
			}

			return titleParams;
		}

		/**
		 * @deprecated - use to AvatarDetail
		 * @return {string | null}
		 */
		getAvatarUrl()
		{
			return this.avatar;
		}

		/**
		 * @return {AvatarDetail}
		 */
		getMentionAvatarProps()
		{
			const avatarProps = this.#getAvatarProps();
			avatarProps.radius = Theme.corner.S.toNumber();
			avatarProps.placeholder.letters.fontSize = 13;

			return avatarProps;
		}

		/**
		 * @return {AvatarDetail}
		 */
		getMessageAvatarProps()
		{
			const avatarProps = this.#getAvatarProps();
			avatarProps.placeholder.letters.fontSize = 15;

			return avatarProps;
		}

		/**
		 * @return {string}
		 */
		getMessageCopilotPromptAvatarUrl()
		{
			const roleData = this.#getCopilotRoleData();

			return withCurrentDomain(roleData?.avatar?.medium || roleData?.avatar?.small);
		}

		/**
		 * @return {AvatarDetail}
		 */
		getRecentItemAvatarProps({ useNotes = true } = {})
		{
			if (useNotes && this.isCurrentUser)
			{
				return this.getAvatarNotesProps();
			}

			const avatarProps = this.#getAvatarProps();
			avatarProps.radius = Theme.corner.M.toNumber();

			return avatarProps;
		}

		/**
		 * @return {AvatarDetail}
		 */
		getDialogHeaderAvatarProps()
		{
			if (this.isCurrentUser)
			{
				return this.getAvatarNotesProps(22);
			}

			const avatarProps = this.#getAvatarProps();
			avatarProps.radius = Theme.corner.S.toNumber();
			avatarProps.placeholder.letters.fontSize = 12;

			return avatarProps;
		}

		/**
		 * @deprecated
		 * @return {AvatarDetail}
		 */
		getSidebarTitleAvatarProps()
		{
			let avatarProps = {};

			if (this.isCurrentUser)
			{
				avatarProps = this.getAvatarNotesProps(48);
			}
			else
			{
				avatarProps = this.#getAvatarProps();
				avatarProps.radius = Theme.corner.L.toNumber();
				avatarProps.placeholder.letters.fontSize = 30;
			}

			avatarProps.style = this.#getSizeStyle(72);

			return avatarProps;
		}

		/**
		 * @deprecated
		 * @return {AvatarDetail}
		 */
		getSidebarTabItemDescriptionAvatarProps()
		{
			return this.#getAvatarProps({
				backBorderWidth: 1,
				placeholder: {
					letters: {
						fontSize: 8,
					},
				},
				style: this.#getSizeStyle(18),
			});
		}

		/**
		 * @return {AvatarDetail}
		 */
		getRecentSearchCarouselAvatarProps()
		{
			return this.getRecentItemAvatarProps();
		}

		/**
		 * @return {AvatarDetail}
		 */
		getRecentSearchItemAvatarProps()
		{
			if (this.isCurrentUser)
			{
				return this.getAvatarNotesProps(28);
			}

			const avatarProps = this.#getAvatarProps();
			avatarProps.radius = Theme.corner.S.toNumber();
			avatarProps.placeholder.letters.fontSize = 15;

			return avatarProps;
		}

		/**
		 * @return {AvatarDetail}
		 */
		getListItemAvatarProps(options = {})
		{
			const { useNotes } = options;
			const { fontSize } = Typography.h5.getValue();

			if (useNotes && this.isCurrentUser)
			{
				return this.getAvatarNotesProps();
			}

			return this.#getAvatarProps({
				placeholder: {
					letters: {
						fontSize,
					},
				},
				style: this.#getSizeStyle(40),
			});
		}

		/**
		 * @public
		 * @param {Partial<AvatarDetail>} customAvatarProps
		 * @return {AvatarDetail}
		 */
		getBaseAvatarProps(customAvatarProps)
		{
			return this.#getAvatarProps(customAvatarProps);
		}

		/**
		 * @return {AvatarDetail}
		 */
		getReactionAvatarProps()
		{
			return this.#getAvatarProps({
				style: this.#getSizeStyle(18),
				placeholder: {
					letters: null,
				},
			});
		}

		/**
		 * @return {AvatarDetail}
		 */
		getMessageCommentInfoAvatarProps()
		{
			return this.#getAvatarProps({
				style: this.#getSizeStyle(28),
				placeholder: {
					letters: null,
				},
			});
		}

		/**
		 * @private
		 * @return {AvatarDetail}
		 */
		getChatAvatarProps()
		{
			switch (this.type)
			{
				case DialogType.collab:
				{
					return this.#getAvatarCollabFields();
				}

				case DialogType.copilot:
				{
					return this.#getAvatarMainRoleCopilotFields();
				}

				case DialogType.channel:
				case DialogType.comment:
				case DialogType.generalChannel:
				case DialogType.openChannel:
				{
					return this.#getAvatarChannelFields();
				}

				default:
				{
					return this.#getAvatarChatFields();
				}
			}
		}

		/**
		 * @private
		 * @return {AvatarDetail}
		 */
		getUserAvatarProps()
		{
			const user = this.getUserById(this.dialogId);
			const userHelper = UserHelper.createByUserId(user?.id);

			if (!userHelper)
			{
				return this.#getAvatarUserFields();
			}

			if (userHelper.isCollaber)
			{
				return this.#getAvatarCollaberFields();
			}

			if (userHelper.isExtranet)
			{
				return this.#getAvatarExtranetFields();
			}

			if (userHelper.isCopilotBot)
			{
				return this.#getAvatarCopilotFields();
			}

			if (userHelper.isAiAssistant)
			{
				return this.#getAvatarAiAssistantFields();
			}

			return this.#getAvatarUserFields();
		}

		/**
		 * @param {number} size
		 * @return {AvatarDetail}
		 */
		getAvatarNotesProps(size = 38)
		{
			const defaultFields = this.#getAvatarDefaultFields();

			if (!Feature.isNotesIconAvailable)
			{
				return {
					...defaultFields,
					uri: withCurrentDomain(`${ASSET_PATH}png/favorite_avatar.png`),
					placeholder: {
						...defaultFields.placeholder,
						backgroundColor: Color.accentMainPrimaryalt.toHex(),
					},
				};
			}

			return {
				...defaultFields,
				uri: null,
				placeholder: {
					...defaultFields.placeholder,
					type: AvatarDetailFields.placeholderType.svg,
					backgroundColor: Color.accentMainPrimaryalt.toHex(),
					svg: {
						named: Icon.BOOKMARK.getIconName(),
						tintColor: Color.baseWhiteFixed.toHex(),
						size,
					},
				},
			};
		}

		/**
		 * @param {Partial<AvatarDetail>} customAvatarProps
		 * @return {AvatarDetail}
		 */
		#getAvatarProps(customAvatarProps = {})
		{
			let avatarProps = {};
			if (DialogHelper.isDialogId(this.dialogId))
			{
				avatarProps = this.getChatAvatarProps();
			}
			else
			{
				avatarProps = this.getUserAvatarProps();
			}

			return merge(avatarProps, customAvatarProps);
		}

		/**
		 * @private
		 * @return {AvatarDetail}
		 */
		#getAvatarMainRoleCopilotFields()
		{
			const defaultFields = this.#getAvatarDefaultFields();

			return {
				...defaultFields,
				...this.#getCopilotFields(),
			};
		}

		/**
		 * @private
		 * @return {AvatarDetail}
		 */
		#getAvatarCopilotFields()
		{
			const copilotRoleData = this.#getCopilotRoleData();
			const smallRoleAvatar = copilotRoleData?.avatar?.small;
			if (smallRoleAvatar)
			{
				this.avatar = encodeURI(smallRoleAvatar);
			}

			const defaultFields = this.#getAvatarDefaultFields();

			return {
				...defaultFields,
				...this.#getCopilotFields(),
			};
		}

		/**
		 * @private
		 * @return {AvatarDetail}
		 */
		#getAvatarCollabFields()
		{
			const defaultFields = this.#getAvatarDefaultFields();

			return {
				...defaultFields,
				...this.#getCollabFields(),
				type: AvatarShape.HEXAGON.value,
			};
		}

		/**
		 * @private
		 * @return {AvatarDetail}
		 */
		#getAvatarCollaberFields()
		{
			const defaultFields = this.#getAvatarDefaultFields();

			return { ...defaultFields, ...this.#getCollabFields() };
		}

		/**
		 * @private
		 * @return {AvatarDetail}
		 */
		#getAvatarExtranetFields()
		{
			const defaultFields = this.#getAvatarDefaultFields();

			return { ...defaultFields, ...this.#getExtranetFields() };
		}

		/**
		 * @return {AvatarDetail}
		 */
		#getAvatarAiAssistantFields()
		{
			const defaultFields = this.#getAvatarDefaultFields();

			return { ...defaultFields, ...this.#getAiAssistantFields() };
		}

		/**
		 * @returns {Partial<AvatarDetail>}
		 */
		#getExtranetFields()
		{
			return {
				accentType: AvatarDetailFields.accentType.orange,
				hideOutline: false,
				backBorderWidth: 2,
			};
		}

		/**
		 * @returns {Partial<AvatarDetail>}
		 */
		#getCollabFields()
		{
			return {
				accentType: AvatarDetailFields.accentType.green,
				hideOutline: false,
				backBorderWidth: 2,
			};
		}

		/**
		 * @returns {Partial<AvatarDetail>}
		 */
		#getCopilotFields()
		{
			if (!Feature.isChatAvatarAccentTypePurpleAvailable)
			{
				return {};
			}

			return {
				accentType: AvatarDetailFields.accentType.purple,
				hideOutline: false,
				backBorderWidth: 2,
			};
		}

		/**
		 * @private
		 * @return {AvatarDetail}
		 */
		#getAvatarChannelFields()
		{
			const defaultFields = this.#getAvatarDefaultFields();
			/** @type {Partial<AvatarDetail>} */
			const channelFields = {
				type: AvatarShape.SQUARE.value,
				radius: Theme.corner.S.toNumber(),
			};

			if (this.extranet)
			{
				return {
					...defaultFields,
					...channelFields,
					...this.#getExtranetFields(),
					hideOutline: true,
				};
			}

			return { ...defaultFields, ...channelFields };
		}

		/**
		 * @private
		 * @return {AvatarDetail}
		 */
		#getAvatarChatFields()
		{
			const defaultFields = this.#getAvatarDefaultFields();

			if (this.extranet)
			{
				return {
					...defaultFields,
					...this.#getExtranetFields(),
					hideOutline: true,
				};
			}

			return defaultFields;
		}

		/**
		 * @private
		 * @return {AvatarDetail}
		 */
		#getAvatarUserFields()
		{
			return this.#getAvatarDefaultFields();
		}

		/**
		 * @returns {Partial<AvatarDetail>}
		 */
		#getAiAssistantFields()
		{
			return {
				hideOutline: false,
				backBorderColor: Theme.colors.bgContentPrimary,
				backBorderWidth: 3,
				accentType: AvatarDetailFields.accentType.blue,
				accentColorGradient: {
					start: '#0176FF',
					middle: '#25C5C0',
					end: '#60FAB2',
					angle: 135,
				},
			};
		}

		/**
		 * @private
		 * @return {AvatarDetail}
		 */
		#getAvatarDefaultFields()
		{
			const defaultFields = ChatAvatar.#defaultAvatarFields;
			defaultFields.uri = this.avatar;
			defaultFields.title = this.title;
			defaultFields.placeholder.backgroundColor = this.color;

			if (!this.checkIsFirstLettersValid())
			{
				const {
					type: placeholderType,
					svg: placeholderSvg,
				} = this.#getDefaultPlaceholder();
				defaultFields.placeholder.type = placeholderType;
				defaultFields.placeholder.svg = placeholderSvg;
			}

			return defaultFields;
		}

		/**
		 * @private
		 * @returns {boolean}
		 */
		checkIsFirstLettersValid()
		{
			return Boolean(getFirstLetters(this.title));
		}

		/**
		 *
		 * @return {string | null}
		 */
		getColor()
		{
			return this.color;
		}

		/**
		 * @deprecated
		 * @return {boolean}
		 */
		getIsSuperEllipseIcon()
		{
			return this.isSuperEllipseIcon;
		}

		isUser(userId)
		{
			const user = this.getUserById(userId);

			return !user.bot && !user.network && !user.connector;
		}

		/**
		 * @desc get name copilot role
		 * @param {string} dialogId
		 * @return {string|null}
		 * @private
		 */
		getCopilotMainRoleAvatar(dialogId)
		{
			const copilotMainRole = this.store.getters['dialoguesModel/copilotModel/getMainRoleByDialogId'](dialogId);

			return copilotMainRole?.avatar?.small ? encodeURI(copilotMainRole?.avatar?.small) : null;
		}

		/**
		 * @return {object}
		 */
		static get #defaultAvatarFields()
		{
			const { accentType, placeholderType } = AvatarDetailFields;

			return {
				type: AvatarShape.CIRCLE.value,
				polygonAngle: 30, // only IOS
				radius: 0,
				accentType: accentType.blue,
				backBorderWidth: 0,
				backColor: '#ffffff', // only IOS
				hideOutline: true,
				uri: null,
				title: '',
				placeholder: {
					type: placeholderType.auto,
					backgroundColor: '#ffffff',
					letters: {
						fontSize: 20,
					},
				},
			};
		}

		/**
		 * @private
		 * @param userId
		 * @return {?UsersModelState|null}
		 */
		getUserById(userId)
		{
			const user = this.store.getters['usersModel/getById'](userId);
			if (user)
			{
				return user;
			}

			const messengerUser = this.messengerStore.getters['usersModel/getById'](userId);
			if (messengerUser)
			{
				return messengerUser;
			}

			return null;
		}

		/**
		 * @private
		 * @param dialogId
		 * @return {?DialoguesModelState|null}
		 */
		getDialogById(dialogId)
		{
			const dialog = this.store.getters['dialoguesModel/getById'](dialogId);
			if (dialog)
			{
				return dialog;
			}

			const messengerDialog = this.messengerStore.getters['dialoguesModel/getById'](dialogId);
			if (messengerDialog)
			{
				return messengerDialog;
			}

			return null;
		}

		/**
		 * @private
		 * @return {object|null}
		 */
		#getCopilotRoleData()
		{
			return serviceLocator.get('core')
				.getStore().getters['dialoguesModel/copilotModel/getRoleByMessageId'](`chat${this.options?.chatId}`, this.options?.messageId);
		}

		#getDefaultUserNamedIcon()
		{
			const user = this.store.getters['usersModel/getById'](this.dialogId);

			switch (user?.type)
			{
				case UserType.extranet:
				case UserType.collaber:
				{
					return DefaultUserNamedIcon[user.type];
				}

				default:
				{
					return DefaultUserNamedIcon[UserType.user];
				}
			}
		}

		#getDefaultPlaceholder()
		{
			const { placeholderType } = AvatarDetailFields;
			const placeholder = {
				type: placeholderType.svg,
				svg: {
					named: this.#getDefaultUserNamedIcon(),
				},
			};

			const isGroup = DialogHelper.isDialogId(this.dialogId);
			if (isGroup)
			{
				placeholder.svg.named = null;
				placeholder.svg.uri = defaultGroupChatAvatar;
			}

			return placeholder;
		}

		/**
		 * @param {number} size
		 * @returns {{width, height}}
		 */
		#getSizeStyle(size)
		{
			return {
				width: Number(size),
				height: Number(size),
			};
		}
	}

	module.exports = {
		ChatAvatar,
	};
});
