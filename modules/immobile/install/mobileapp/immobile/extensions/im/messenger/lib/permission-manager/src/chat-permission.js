/**
 * @module im/messenger/lib/permission-manager/chat-permission
 */
jn.define('im/messenger/lib/permission-manager/chat-permission', (require, exports, module) => {
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Type } = require('type');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { UserRole, DialogActionType, DialogType } = require('im/messenger/const');

	class ChatPermission
	{
		constructor()
		{
			this.dialogData = Object.create(null);
		}

		/**
		 * @desc check is can call by dialog data (use id dialog "chat#" or dialog state object)
		 * @param {DialoguesModelState|string} dialogData
		 * @param {boolean} [verbose=false] - prop for verbose response, returns an object with a key
		 * @return {boolean|object}
		 */
		isCanCall(dialogData, verbose = false)
		{
			if (!this.setDialogData(dialogData))
			{
				return false;
			}

			const isHTTPS = this.isHTTPS();
			const userLimit = this.getCallUsersLimit();
			const isMoreOne = this.dialogData.userCounter > 1;
			const isLimit = this.dialogData.userCounter > userLimit;
			const isEntityType = this.isCanCallByEntityType(this.dialogData.entityType);
			const isDialogType = this.isCanCallByDialogType(this.dialogData.type);
			const isCanCall = isHTTPS && isMoreOne && !isLimit && isEntityType && isDialogType;

			if (verbose)
			{
				return {
					isCanCall,
					isHTTPS,
					isMoreOne,
					isLimit,
					isEntityType,
					isDialogType,
				};
			}

			return isCanCall;
		}

		/**
		 * @desc Check is can add participants to chat
		 * @param {DialoguesModelState|string} dialogData
		 * @return {boolean}
		 */
		isCanAddParticipants(dialogData)
		{
			if (!this.setDialogData(dialogData))
			{
				return false;
			}

			if (this.dialogData.permissions)
			{
				return this.iaCanAddBySettingChat() && this.iaCanAddByTypeChat();
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
		iaCanAddByTypeChat()
		{
			const rolesByChatType = this.getInstalledRolesByChatType();
			const installedMinimalRole = rolesByChatType[DialogActionType.extend];

			return this.getRightByLowRole(installedMinimalRole);
		}

		/**
		 * @desc Get object with minimal installed roles by chat type
		 * @return {object}
		 */
		getInstalledRolesByChatType()
		{
			const chatPermissions = this.getChatPermissions();
			let chatType = this.dialogData.type;

			if (Type.isUndefined(chatPermissions.byChatType[chatType]))
			{
				chatType = DialogType.default;
			}

			return chatPermissions.byChatType[chatType] || {};
		}

		/**
		 * @desc Check is can remove participants to chat
		 * @param {DialoguesModelState|string} dialogData
		 * @return {boolean}
		 */
		isCanRemoveParticipants(dialogData)
		{
			if (!this.setDialogData(dialogData))
			{
				return false;
			}

			if (this.dialogData.permissions)
			{
				return this.isCanRemoveBySettingChat() && this.isCanRemoveByTypeChat();
			}

			return this.isOwner();
		}

		/**
		 * @desc Check is can remove participants by setting chat
		 * @return {boolean}
		 */
		isCanRemoveBySettingChat()
		{
			const installedRole = this.dialogData.permissions.manageUsersDelete;

			return this.getRightByLowRole(installedRole);
		}

		/**
		 * @desc Check is can remove participant by role type chat
		 * @return {boolean}
		 */
		isCanRemoveByTypeChat()
		{
			const rolesByChatType = this.getInstalledRolesByChatType();
			const installedMinimalRole = rolesByChatType[DialogActionType.leave]; // leave equal kick

			return this.getRightByLowRole(installedMinimalRole);
		}

		/**
		 * @desc Check is can leave from chat
		 * @param {DialoguesModelState|string} dialogData
		 * @return {boolean}
		 */
		isCanLeaveFromChat(dialogData)
		{
			if (!this.setDialogData(dialogData))
			{
				return false;
			}

			if (this.dialogData)
			{
				return this.iaCanLeaveByTypeChat();
			}

			return false;
		}

		/**
		 * @desc Check is can remove participant by role type chat
		 * @return {boolean}
		 * @private
		 */
		iaCanLeaveByTypeChat()
		{
			const rolesByChatType = this.getInstalledRolesByChatType();
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
		isCanRemoveUserById(userId, dialogData)
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
			const currentRole = this.dialogData.role;

			switch (currentRole)
			{
				case UserRole.owner:
					return true;
				case UserRole.manager:
					return compareRole === currentRole || compareRole === UserRole.member;
				case UserRole.member:
					return compareRole === currentRole;
				default: return false;
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
				this.dialogData = dialogData;
			}

			return true;
		}

		/**
		 * @desc check is https
		 * @return {boolean}
		 */
		isHTTPS()
		{
			return currentDomain.startsWith('https://');
		}

		/**
		 * @desc Returns count max user for call
		 * @return {number} userLimit
		 */
		getCallUsersLimit()
		{
			// eslint-disable-next-line no-undef
			const { call_server_max_users: userLimit } = jnExtensionData.get('im:messenger/lib/permission-manager');

			return userLimit;
		}

		getChatOptions()
		{
			// eslint-disable-next-line no-undef
			const { userChatOptions } = jnExtensionData.get('im:messenger/lib/permission-manager');

			return userChatOptions;
		}

		getChatPermissions()
		{
			// eslint-disable-next-line no-undef
			const { permissions = {} } = jnExtensionData.get('im:messenger/lib/permission-manager');

			return permissions;
		}

		/**
		 * @desc check is can call by entity type dialog ( chat )
		 * @param {string} entityType
		 * @return {boolean}
		 */
		isCanCallByEntityType(entityType)
		{
			const userChatOptions = this.getChatOptions();
			if (entityType in userChatOptions)
			{
				return userChatOptions[entityType].CALL;
			}

			return userChatOptions.DEFAULT.CALL;
		}

		/**
		 * @desc check is can call by dialog type ( chat )
		 * @param {string} type
		 * @return {boolean}
		 */
		isCanCallByDialogType(type)
		{
			return type !== DialogType.copilot;
		}

		/**
		 * @desc Returns is owner chat
		 * @return {boolean}
		 * @private
		 */
		isOwner()
		{
			const currentUserId = MessengerParams.getUserId();

			return this.dialogData.owner === currentUserId;
		}

		/**
		 * @desc Check is can post message to current chat
		 * @param {DialoguesModelState|string} dialogData
		 * @return {boolean}
		 */
		isCanPost(dialogData)
		{
			if (!this.setDialogData(dialogData))
			{
				return false;
			}

			if (Type.isUndefined(this.dialogData.permissions)
				|| Type.isUndefined(this.dialogData.permissions.canPost)
				|| this.dialogData.permissions.canPost === UserRole.none
			)
			{
				return true;
			}

			return this.getRightByLowRole(this.dialogData.permissions.canPost);
		}
	}

	module.exports = {
		ChatPermission: new ChatPermission(),
	};
});
