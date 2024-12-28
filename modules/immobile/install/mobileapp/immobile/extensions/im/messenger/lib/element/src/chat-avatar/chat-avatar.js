/**
 * @module im/messenger/lib/element/chat-avatar
 */
jn.define('im/messenger/lib/element/chat-avatar', (require, exports, module) => {
	const { Type } = require('type');
	const { Typography } = require('tokens');
	const { Theme } = require('im/lib/theme');
	const { merge } = require('utils/object');

	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { MessengerParams } = require('im/messenger/lib/params');
	const {
		DialogType,
		UserColor,
		UserType,
	} = require('im/messenger/const');
	const { AvatarShape } = require('ui-system/blocks/avatar');

	const AvatarDetailFields = Object.freeze({
		accentType: {
			orange: 'orange',
			green: 'green',
			blue: 'blue',
		},
		placeholderType: {
			letters: 'letters',
			svg: 'svg',
			none: 'none',
		},
	});

	/**
	 * @class ChatAvatar
	 */
	class ChatAvatar
	{
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
			this.store = serviceLocator.get('core').getStore();
			this.messengerStore = serviceLocator.get('core').getMessengerStore();
			this.avatar = null;
			this.color = null;
			this.title = null;
			this.isSuperEllipseIcon = false;
			this.type = null;
			this.dialogId = dialogId;

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
			return `${currentDomain}/bitrix/mobileapp/immobile/extensions/im/messenger/lib/element/src/chat-avatar/images/`;
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
				this.avatar = this.getCopilotRoleAvatar(dialogModel.dialogId) || `${ChatAvatar.getImagePath()}avatar_copilot_assistant.png`;
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
			const avatarProps = this.getAvatarProps();
			avatarProps.radius = Theme.corner.S.toNumber();
			avatarProps.placeholder.letters.fontSize = 13;

			return avatarProps;
		}

		/**
		 * @return {AvatarDetail}
		 */
		getMessageAvatarProps()
		{
			const avatarProps = this.getAvatarProps();
			avatarProps.placeholder.letters.fontSize = 15;

			return avatarProps;
		}

		/**
		 * @return {AvatarDetail}
		 */
		getRecentItemAvatarProps()
		{
			const avatarProps = this.getAvatarProps();
			avatarProps.radius = Theme.corner.M.toNumber();

			return avatarProps;
		}

		/**
		 * @return {AvatarDetail}
		 */
		getDialogHeaderAvatarProps()
		{
			const avatarProps = this.getAvatarProps();
			avatarProps.radius = Theme.corner.S.toNumber();
			avatarProps.placeholder.letters.fontSize = 12;

			return avatarProps;
		}

		/**
		 * @return {AvatarDetail}
		 */
		getSidebarTitleAvatarProps()
		{
			const avatarProps = this.getAvatarProps();
			avatarProps.radius = Theme.corner.L.toNumber();
			avatarProps.placeholder.letters.fontSize = 30;
			avatarProps.style = {
				width: 72,
				height: 72,
			};

			return avatarProps;
		}

		/**
		 * @return {AvatarDetail}
		 */
		getSidebarTabItemDescriptionAvatarProps()
		{
			return this.getAvatarProps({
				backBorderWidth: 1,
				placeholder: {
					letters: {
						fontSize: 8,
					},
				},
				style: {
					width: 18,
					height: 18,
				},
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
			const avatarProps = this.getAvatarProps();
			avatarProps.radius = Theme.corner.S.toNumber();
			avatarProps.placeholder.letters.fontSize = 15;

			return avatarProps;
		}

		/**
		 * @return {AvatarDetail}
		 */
		getListItemAvatarProps()
		{
			return this.getAvatarProps({
				placeholder: {
					letters: {
						fontSize: Typography.h5.getValue().fontSize,
					},
				},
				style: {
					width: 40,
					height: 40,
				},
			});
		}

		/**
		 * @return {AvatarDetail}
		 */
		getReactionAvatarProps()
		{
			return this.getAvatarProps({
				placeholder: {
					letters: {
						fontSize: 9,
					},
				},
				style: {
					width: 18,
					height: 18,
				},
			});
		}

		/**
		 * @return {AvatarDetail}
		 */
		getMessageCommentInfoAvatarProps()
		{
			return this.getAvatarProps({
				placeholder: {
					letters: {
						fontSize: 9,
					},
				},
				style: {
					width: 28,
					height: 28,
				},
			});
		}

		/**
		 * @private
		 * @param {Partial<AvatarDetail>} customAvatarProps
		 * @return {AvatarDetail}
		 */
		getAvatarProps(customAvatarProps = {})
		{
			// eslint-disable-next-line init-declarations
			let avatarProps;
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
		getChatAvatarProps()
		{
			switch (this.type)
			{
				case DialogType.collab:
				{
					return this.#getAvatarCollabFields();
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
			if (!user)
			{
				return this.#getAvatarUserFields();
			}

			// eslint-disable-next-line sonarjs/no-small-switch
			switch (user.type)
			{
				case UserType.collaber:
				{
					return this.#getAvatarCollaberFields();
				}

				case UserType.extranet:
				{
					return this.#getAvatarExtranetFields();
				}

				default:
				{
					return this.#getAvatarUserFields();
				}
			}
		}

		/**
		 * @private
		 * @return {AvatarDetail}
		 */
		#getAvatarCollabFields()
		{
			const defaultFields = this.#getAvatarDefaultFields();
			const collabFields = {
				type: AvatarShape.HEXAGON.value,
				accentType: AvatarDetailFields.accentType.green,
				hideOutline: false,
				backBorderWidth: 2,
			};

			return { ...defaultFields, ...collabFields };
		}

		/**
		 * @private
		 * @return {AvatarDetail}
		 */
		#getAvatarCollaberFields()
		{
			const defaultFields = this.#getAvatarDefaultFields();
			const collaberFields = {
				accentType: AvatarDetailFields.accentType.green,
				hideOutline: false,
				backBorderWidth: 2,
			};

			return { ...defaultFields, ...collaberFields };
		}

		/**
		 * @private
		 * @return {AvatarDetail}
		 */
		#getAvatarExtranetFields()
		{
			const defaultFields = this.#getAvatarDefaultFields();
			const collaberFields = {
				accentType: AvatarDetailFields.accentType.orange,
				hideOutline: false,
				backBorderWidth: 2,
			};

			return { ...defaultFields, ...collaberFields };
		}

		/**
		 * @private
		 * @return {AvatarDetail}
		 */
		#getAvatarChannelFields()
		{
			const defaultFields = this.#getAvatarDefaultFields();
			const channelFields = {
				type: AvatarShape.SQUARE.value,
				radius: Theme.corner.S.toNumber(),
			};

			return { ...defaultFields, ...channelFields };
		}

		/**
		 * @private
		 * @return {AvatarDetail}
		 */
		#getAvatarChatFields()
		{
			return this.#getAvatarDefaultFields();
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
		 * @private
		 * @return {AvatarDetail}
		 */
		#getAvatarDefaultFields()
		{
			const defaultFields = ChatAvatar.#defaultAvatarFields;
			defaultFields.uri = this.avatar;
			defaultFields.title = this.title;
			defaultFields.placeholder.backgroundColor = this.color;

			return defaultFields;
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
		getCopilotRoleAvatar(dialogId)
		{
			const copilotMainRole = this.store.getters['dialoguesModel/copilotModel/getMainRoleByDialogId'](dialogId);

			return copilotMainRole?.avatar?.small ? encodeURI(copilotMainRole?.avatar?.small) : null;
		}

		/**
		 * @return {object}
		 */
		static get #defaultAvatarFields()
		{
			return {
				type: AvatarShape.CIRCLE.value,
				polygonAngle: 30, // only IOS
				radius: 0,
				accentType: AvatarDetailFields.accentType.blue,
				backBorderWidth: 0,
				backColor: '#FFFFFF', // only IOS
				hideOutline: true,
				uri: null,
				title: '',
				placeholder: {
					type: AvatarDetailFields.placeholderType.letters,
					backgroundColor: '#FFFFFF',
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
	}

	module.exports = {
		ChatAvatar,
	};
});
