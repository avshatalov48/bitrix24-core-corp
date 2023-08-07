/**
 * @module im/messenger/lib/permission-manager/chat-permission
 */
jn.define('im/messenger/lib/permission-manager/chat-permission', (require, exports, module) => {
	const { core } = require('im/messenger/core');
	const { Type } = require('type');
	const { MessengerParams } = require('im/messenger/lib/params');

	class ChatPermission
	{
		constructor() {
			this.dialogData = Object.create(null);
		}

		/**
		 * @desc check is can call by dialog data ( use id dialog "chat#" or dialog state object )
		 * @param {DialoguesModelState|string} dialogData
		 * @param {boolean} [verbose=false] - prop for verbose response, returns object with key
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
			const isCanCall = isHTTPS && isMoreOne && !isLimit && isEntityType;

			if (verbose)
			{
				return {
					isCanCall,
					isHTTPS,
					isMoreOne,
					isLimit,
					isEntityType,
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

			// while here one rule, but future will be roles
			return this.isOwner();
		}

		/**
		 * @desc Check is can remove participants to chat
		 * @param {DialoguesModelState|string} dialogData
		 * @return {boolean}
		 */
		// eslint-disable-next-line sonarjs/no-identical-functions
		isCanRemoveParticipants(dialogData)
		{
			if (!this.setDialogData(dialogData))
			{
				return false;
			}

			// while here one rule, but future will be roles
			return this.isOwner();
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
				this.store = core.getStore();
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
		 * @desc Returns is owner chat
		 * @return {boolean}
		 * @private
		 */
		isOwner()
		{
			const currentUserId = MessengerParams.getUserId();

			return this.dialogData.owner === currentUserId;
		}
	}

	module.exports = {
		ChatPermission: new ChatPermission(),
	};
});
