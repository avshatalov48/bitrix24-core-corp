/**
 * @module im/messenger/controller/sidebar-v2/controller/base/src/permission-manager
 */
jn.define('im/messenger/controller/sidebar-v2/controller/base/src/permission-manager', (require, exports, module) => {
	const { Type } = require('type');
	const { ChatPermission, UserPermission } = require('im/messenger/lib/permission-manager');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Loc } = require('im/messenger/controller/sidebar-v2/loc');

	class SidebarPermissionManager
	{
		constructor({ dialogId, dialogHelper })
		{
			this.dialogId = dialogId;
			this.dialogHelper = dialogHelper;
			this.chatPermission = ChatPermission;
			this.userPermission = UserPermission;
			this.userId = serviceLocator.get('core').getUserId();
		}

		canPin()
		{
			const store = serviceLocator.get('core').getStore();
			const recentItem = store.getters['recentModel/getById'](this.dialogId);

			return Type.isObject(recentItem);
		}

		canCall()
		{
			return this.chatPermission.canCall(this.dialogId);
		}

		canEdit()
		{
			return this.chatPermission.canEditDialog(this.dialogId);
		}

		canLeave()
		{
			return this.chatPermission.canLeaveFromChat(this.dialogId);
		}

		canDelete()
		{
			return this.dialogHelper.canBeDeleted;
		}

		canClearHistory()
		{
			return this.dialogHelper.canClearHistory;
		}

		canCopyLink()
		{
			return this.dialogHelper.canCopyChatLink;
		}

		canRemoveUserById(userId, dialogData)
		{
			return ChatPermission.canRemoveUserById(userId, dialogData);
		}

		canRemoveParticipants()
		{
			return ChatPermission.canRemoveParticipants(this.dialogId);
		}

		canMention()
		{
			return ChatPermission.canMention(this.dialogId);
		}

		canAddParticipants()
		{
			return this.chatPermission.canAddParticipants(this.dialogId);
		}

		canChangeOwner()
		{
			return this.chatPermission.canChangeOwner();
		}

		canHide()
		{
			return this.dialogHelper.isBot && !this.dialogHelper.isAiAssistant;
		}

		canMute()
		{
			return this.chatPermission.сanMute(this.dialogId);
		}

		/**
		 * @return {string|null}
		 */
		getCallForbiddenReason()
		{
			const currentContext = this.chatPermission.canCall(this.dialogId, true);

			if (currentContext)
			{
				if (currentContext.isMoreOne === false)
				{
					return Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_CALL_ERROR_MOREONE');
				}

				if (currentContext.isLimit === true)
				{
					return Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_CALL_ERROR_LIMIT');
				}
			}

			return null;
		}
	}

	module.exports = { SidebarPermissionManager };
});
