/**
 * @module im/messenger/controller/sidebar-v2/controller/copilot/src/permission-manager
 */
jn.define('im/messenger/controller/sidebar-v2/controller/copilot/src/permission-manager', (require, exports, module) => {
	const { SidebarPermissionManager } = require('im/messenger/controller/sidebar-v2/controller/base');

	class CopilotSidebarPermissionManager extends SidebarPermissionManager
	{
		canLeave()
		{
			return this.chatPermission.canLeaveFromChat(this.dialogId)
				&& this.dialogHelper.dialogModel.userCounter > 2;
		}
	}

	module.exports = { CopilotSidebarPermissionManager };
});
