/**
 * @module im/messenger/lib/permission-manager/chat-permission
 */
jn.define('im/messenger/lib/permission-manager/chat-permission', (require, exports, module) => {
	const { Type } = require('type');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { MessengerParams } = require('im/messenger/lib/params');
	const {
		UserRole,
		DialogActionType,
		DialogType,
	} = require('im/messenger/const');

	const MinimalRoleForAction = {
		[DialogActionType.readMessage]: UserRole.member,
		[DialogActionType.partialQuote]: UserRole.member,
		[DialogActionType.setReaction]: UserRole.member,
		[DialogActionType.openMessageMenu]: UserRole.member,
		[DialogActionType.openAvatarMenu]: UserRole.member,
		[DialogActionType.openSidebarMenu]: UserRole.member,
		[DialogActionType.followComments]: UserRole.member,
		[DialogActionType.reply]: UserRole.member,
		[DialogActionType.mention]: UserRole.member,
		[DialogActionType.mute]: UserRole.guest,

		[DialogActionType.openComments]: UserRole.guest,
		[DialogActionType.openSidebar]: UserRole.guest,
	};

	class ChatPermission
	{
		constructor()
		{
			/**
			 * @type {DialoguesModelState}
			 */
			this.dialogData = Object.create(null);
		}

		/**
		 * @desc check is can call by dialog data (use id dialog "chat#" or dialog state object)
		 * @param {DialoguesModelState|string} dialogData
		 * @param {boolean} [verbose=false] - prop for verbose response, returns an object with a key
		 * @return {boolean|object}
		 */
		canCall(dialogData, verbose = false)
		{
			if (!this.setDialogData(dialogData))
			{
				return false;
			}

			const userLimit = this.getCallUsersLimit();
			const isMoreOne = this.dialogData.userCounter > 1;
			const isLimit = this.dialogData.userCounter > userLimit;
			const isEntityType = this.canCallByEntityType(this.dialogData.entityType);
			const isDialogType = this.canCallByDialogType(this.dialogData.type);
			const canCall = isMoreOne && !isLimit && isEntityType && isDialogType;

			if (verbose)
			{
				return {
					canCall,
					isMoreOne,
					isLimit,
					isEntityType,
					isDialogType,
				};
			}

			return canCall;
		}

		/**
		 * @desc Check is can add participants to chat
		 * @param {DialoguesModelState|string} dialogData
		 * @return {boolean}
		 */
		canAddParticipants(dialogData)
		{
			if (!this.setDialogData(dialogData))
			{
				return false;
			}

			if (this.dialogData.permissions)
			{
				return this.iaCanAddBySettingChat() && this.canAddByTypeChat();
			}

			return this.isOwner();
		}

		/**
		 * @desc Check is can add participant by role setting chat
		 * @return {boolean}
		 */
		iaCanAddBySettingChat()
		{
			const installedRole = this.dialogData.permissions.manageUsersAdd;

			return this.getRightByLowRole(installedRole);
		}

		/**
		 * @desc Check is can add participant by role type chat
		 * @return {boolean}
		 */
		canAddByTypeChat()
		{
			const rolesByChatType = this.getDefaultRolesByChatType();
			const installedMinimalRole = rolesByChatType[DialogActionType.extend];

			return this.getRightByLowRole(installedMinimalRole);
		}

		/**
		 * @desc Get object with minimal installed roles by chat type
		 * @return {object}
		 */
		getDefaultRolesByChatType()
		{
			return this.getPermissionByChatType(this.dialogData.type);
		}

		getPermissionByChatType(chatType)
		{
			const chatPermissions = this.getChatPermissions();
			if (!chatPermissions?.byChatType)
			{
				return {};
			}

			// we can have a "user" type for "private" chats
			const currentChatType = chatType === DialogType.user ? DialogType.private : chatType;

			return chatPermissions.byChatType[currentChatType] ?? chatPermissions.byChatType[DialogType.default];
		}

		getActionGroupsByChatType(chatType)
		{
			const chatPermissions = this.getChatPermissions();
			if (!chatPermissions?.actionGroupsDefaults)
			{
				return {};
			}

			return chatPermissions.actionGroupsDefaults[chatType] ?? chatPermissions.actionGroupsDefaults[DialogType.default];
		}

		/**
		 * @desc Check is can remove participants to chat
		 * @param {DialoguesModelState|string} dialogData
		 * @return {boolean}
		 */
		canRemoveParticipants(dialogData)
		{
			if (!this.setDialogData(dialogData))
			{
				return false;
			}

			if (this.dialogData.permissions)
			{
				return this.canRemoveBySettingChat() && this.canRemoveByTypeChat();
			}

			return this.isOwner();
		}

		/**
		 * @desc Check is can change owner for chat
		 * @param {DialoguesModelState|string} dialogData
		 * @return {boolean}
		 */
		canChangeOwner(dialogData)
		{
			if (!this.setDialogData(dialogData))
			{
				return false;
			}

			if (this.dialogData.permissions)
			{
				return this.canChangeOwnerByTypeChat();
			}

			return false;
		}

		/**
		 * @desc Check is can remove participants by setting chat
		 * @return {boolean}
		 */
		canRemoveBySettingChat()
		{
			const installedRole = this.dialogData.permissions.manageUsersDelete;

			return this.getRightByLowRole(installedRole);
		}

		/**
		 * @desc Check is can remove participant by role type chat
		 * @return {boolean}
		 */
		canRemoveByTypeChat()
		{
			const rolesByChatType = this.getDefaultRolesByChatType();
			const installedMinimalRole = rolesByChatType[DialogActionType.kick];

			return this.getRightByLowRole(installedMinimalRole);
		}

		/**
		 * @desc Check is can change owner by role type chat
		 * @return {boolean}
		 */
		canChangeOwnerByTypeChat()
		{
			const rolesByChatType = this.getDefaultRolesByChatType();
			const installedMinimalRole = rolesByChatType[DialogActionType.changeOwner];

			return this.getRightByLowRole(installedMinimalRole);
		}

		/**
		 * @desc Check is can edit chat
		 * @param {DialoguesModelState|string} dialogData
		 * @return {boolean}
		 */
		canEditDialog(dialogData)
		{
			if (!this.setDialogData(dialogData))
			{
				return false;
			}
			const rolesByChatType = this.getDefaultRolesByChatType();
			const installedMinimalRoleForEdit = rolesByChatType[DialogActionType.rename];
			if (installedMinimalRoleForEdit === UserRole.none)
			{
				return false;
			}

			if (this.canUpdateDialogByRole(dialogData))
			{
				return true;
			}

			return this.canManageUIDialog(dialogData);
		}

		/**
		 * @param {DialoguesModelState|string} dialogData
		 * @return {boolean}
		 */
		canUpdateDialogByRole(dialogData)
		{
			if (!this.setDialogData(dialogData))
			{
				return false;
			}
			const rolesByChatType = this.getDefaultRolesByChatType();
			const installedMinimalRole = rolesByChatType[DialogActionType.update];

			return this.getRightByLowRole(installedMinimalRole);
		}

		/**
		 * @desc Check is can edit dialog UI
		 * @param {DialoguesModelState|string} dialogData
		 * @return {boolean}
		 */
		canManageUIDialog(dialogData)
		{
			if (!this.setDialogData(dialogData))
			{
				return false;
			}

			const editUIRole = this.dialogData?.permissions.manageUi;

			return this.getRightByLowRole(editUIRole);
		}

		/**
		 * @desc Check is can leave from chat
		 * @param {DialoguesModelState|string} dialogData
		 * @return {boolean}
		 */
		canLeaveFromChat(dialogData)
		{
			if (!this.setDialogData(dialogData))
			{
				return false;
			}

			if (this.dialogData)
			{
				return this.canLeaveByTypeChat();
			}

			return false;
		}

		/**
		 * @desc Check is can remove participant by role type chat
		 * @return {boolean}
		 * @private
		 */
		canLeaveByTypeChat()
		{
			const rolesByChatType = this.getDefaultRolesByChatType();
			let actionType = DialogActionType.leave;

			const isOwner = this.isOwner();
			if (isOwner)
			{
				actionType = DialogActionType.leaveOwner;
			}
			const installedMinimalRole = rolesByChatType[actionType];

			return this.getRightByLowRole(installedMinimalRole);
		}

		/**
		 * @desc Check is can remove participants to chat
		 * @param {number|string} userId
		 * @param {DialoguesModelState|string} dialogData
		 * @return {boolean}
		 */
		canRemoveUserById(userId, dialogData)
		{
			if (!this.setDialogData(dialogData))
			{
				return false;
			}

			if (this.dialogData.permissions)
			{
				const deletingUserRole = this.findRoleById(userId, this.dialogData);

				return this.getRightByLowRole(deletingUserRole);
			}

			return this.isOwner();
		}

		/**
		 * @desc Get right by lower role
		 * @param {string} compareRole
		 * @return {boolean}
		 */
		getRightByLowRole(compareRole)
		{
			if (compareRole === UserRole.none)
			{
				return false;
			}

			const currentRole = this.dialogData.role;

			switch (currentRole)
			{
				case UserRole.owner:
					return true;
				case UserRole.manager:
					return [UserRole.manager, UserRole.member, UserRole.guest].includes(compareRole);
				case UserRole.member:
					return [UserRole.member, UserRole.guest].includes(compareRole);
				default:
					return false;
			}
		}

		/**
		 * @desc Get role user by id from dialog data
		 * @param {number} userId
		 * @param {DialoguesModelState} dialogData
		 * @return {string}
		 */
		findRoleById(userId, dialogData)
		{
			if (Type.isUndefined(dialogData))
			{
				return UserRole.none;
			}

			if (userId === dialogData.owner)
			{
				return UserRole.owner;
			}

			if (dialogData.managerList.includes(userId))
			{
				return UserRole.manager;
			}

			return UserRole.member;
		}

		/**
		 * @desc Set data dialog
		 * @param {DialoguesModelState|string} dialogData
		 * @return {boolean}
		 * @private
		 */
		setDialogData(dialogData)
		{
			if (Type.isString(dialogData))
			{
				this.store = serviceLocator.get('core').getStore();
				const dialogState = this.store.getters['dialoguesModel/getById'](dialogData);
				if (Type.isUndefined(dialogState))
				{
					return false;
				}

				this.dialogData = dialogState;
			}

			if (Type.isObject(dialogData))
			{
				if (Type.isUndefined(dialogData.permissions))
				{
					return false;
				}

				this.dialogData = dialogData;
			}

			return true;
		}

		/**
		 * @desc Returns count max user for call
		 * @return {number} userLimit
		 */
		getCallUsersLimit()
		{
			// eslint-disable-next-line no-undef
			const userLimit = MessengerParams.get('CALL_SERVER_MAX_USERS', null)
				?? jnExtensionData.get('im:messenger/lib/permission-manager').call_server_max_users
			;
			return userLimit;
		}

		getChatOptions()
		{
			// eslint-disable-next-line no-undef
			const { userChatOptions } = jnExtensionData.get('im:messenger/lib/permission-manager');

			return userChatOptions;
		}

		/**
		 * @return {Permissions}
		 */
		getChatPermissions()
		{
			return MessengerParams.getPermissions();
		}

		/**
		 * @desc check is can call by entity type dialog ( chat )
		 * @param {string} entityType
		 * @return {boolean}
		 */
		canCallByEntityType(entityType)
		{
			const userChatOptions = this.getChatOptions();
			if (entityType in userChatOptions)
			{
				return userChatOptions[entityType].CALL;
			}

			return userChatOptions.DEFAULT.CALL;
		}

		/**
		 * @desc Check is can change message auto delete delay
		 * @param {DialoguesModelState|string} dialogData
		 * @return {boolean}
		 */
		canChangeMessagesAutoDeleteDelay(dialogData)
		{
			if (!this.setDialogData(dialogData))
			{
				return false;
			}

			const rolesByChatType = this.getDefaultRolesByChatType();
			const installedMinimalRole = rolesByChatType[DialogActionType.changeMessagesAutoDeleteDelay];

			return this.getRightByLowRole(installedMinimalRole);
		}

		/**
		 * @desc check is can call by dialog type ( chat )
		 * @param {string} type
		 * @return {boolean}
		 */
		canCallByDialogType(type)
		{
			return ![
				DialogType.copilot,
				DialogType.channel,
				DialogType.openChannel,
				DialogType.generalChannel,
				DialogType.comment,
			].includes(type);
		}

		/**
		 * @desc Returns is owner chat
		 * @param {?DialoguesModelState} dialogData
		 * @return {boolean}
		 */
		isOwner(dialogData = null)
		{
			const currentUserId = MessengerParams.getUserId();
			const dialogModelState = dialogData || this.dialogData;

			return dialogModelState.owner === currentUserId;
		}

		/**
		 * @desc Check is can post message to current chat
		 * @param {DialoguesModelState|string} dialogData
		 * @return {boolean}
		 */
		canPost(dialogData)
		{
			if (!this.setDialogData(dialogData))
			{
				return false;
			}

			if (
				Type.isUndefined(this.dialogData.permissions)
				|| Type.isUndefined(this.dialogData.permissions.manageMessages)
			)
			{
				return true;
			}

			return this.getRightByLowRole(this.dialogData.permissions.manageMessages);
		}

		canReply(dialogData)
		{
			if (!this.setDialogData(dialogData))
			{
				return false;
			}
			const minimalRole = MinimalRoleForAction[DialogActionType.reply];
			if (!this.#checkMinimalRole(minimalRole, this.dialogData.role))
			{
				return false;
			}

			return this.canPost(dialogData);
		}

		canMention(dialogData)
		{
			if (!this.setDialogData(dialogData))
			{
				return false;
			}
			const minimalRole = MinimalRoleForAction[DialogActionType.mention];
			if (!this.#checkMinimalRole(minimalRole, this.dialogData.role))
			{
				return false;
			}

			return this.canPost(dialogData);
		}

		canOpenMessageMenu(dialogData)
		{
			if (!this.setDialogData(dialogData))
			{
				return false;
			}
			const minimalRole = MinimalRoleForAction[DialogActionType.openMessageMenu];

			return this.#checkMinimalRole(minimalRole, this.dialogData.role);
		}

		canOpenAvatarMenu(dialogData)
		{
			if (!this.setDialogData(dialogData))
			{
				return false;
			}
			const minimalRole = MinimalRoleForAction[DialogActionType.openAvatarMenu];

			return this.#checkMinimalRole(minimalRole, this.dialogData.role);
		}

		canDeleteOtherMessage(dialogData)
		{
			if (!this.setDialogData(dialogData))
			{
				return false;
			}

			const rolesByChatType = this.getDefaultRolesByChatType();
			const installedMinimalRole = rolesByChatType[DialogActionType.deleteOthersMessage];

			return this.getRightByLowRole(installedMinimalRole);
		}

		canDeleteChat(dialogData)
		{
			if (!this.setDialogData(dialogData))
			{
				return false;
			}

			const rolesByChatType = this.getDefaultRolesByChatType();
			const installedMinimalRole = rolesByChatType[DialogActionType.delete];

			return this.getRightByLowRole(installedMinimalRole);
		}

		/**
		 * @param {DialoguesModelState|string} dialogData
		 * @return {boolean}
		 */
		canSetReaction(dialogData)
		{
			if (!this.setDialogData(dialogData))
			{
				return false;
			}
			const minimalRole = MinimalRoleForAction[DialogActionType.setReaction];

			return this.#checkMinimalRole(minimalRole, this.dialogData.role);
		}

		/**
		 * @param {DialoguesModelState|string} dialogData
		 * @return {boolean}
		 */
		сanMute(dialogData)
		{
			if (!this.setDialogData(dialogData))
			{
				return false;
			}
			const minimalRole = MinimalRoleForAction[DialogActionType.mute];

			return this.#checkMinimalRole(minimalRole, this.dialogData.role);
		}

		#checkMinimalRole(minimalRole, roleToCheck)
		{
			if (minimalRole === UserRole.none)
			{
				return false;
			}

			const roleWeights = {};
			Object.values(UserRole).forEach((role, index) => {
				roleWeights[role] = index;
			});

			return roleWeights[roleToCheck] >= roleWeights[minimalRole];
		}
	}

	module.exports = {
		ChatPermission: new ChatPermission(),
	};
});
