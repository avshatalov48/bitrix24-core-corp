/**
 * @module im/messenger/controller/sidebar-v2/user-actions/alerts
 */
jn.define('im/messenger/controller/sidebar-v2/user-actions/alerts', (require, exports, module) => {
	const {
		showLeaveChatAlert,
		showLeaveChannelAlert,
		showLeaveCollabAlert,

		showDeleteChatAlert,
		showDeleteChannelAlert,
		showDeleteCollabAlert,

		showClearHistoryChatAlert,
		showClearHistoryCollabAlert,
	} = require('im/messenger/lib/ui/alert');
	const { ToastType } = require('im/messenger/lib/ui/notification');

	const { resolveSidebarType, SidebarType } = require('im/messenger/controller/sidebar-v2/factory');

	/**
	 * @param {string} dialogId
	 * @return {function}
	 */
	function resolveLeaveDialogConfirmFn(dialogId)
	{
		const sidebarType = resolveSidebarType(dialogId);

		switch (sidebarType)
		{
			case SidebarType.channel:
				return showLeaveChannelAlert;

			case SidebarType.collab:
				return showLeaveCollabAlert;

			default:
				return showLeaveChatAlert;
		}
	}

	/**
	 * @param {string} dialogId
	 * @return {function}
	 */
	function resolveDeleteDialogConfirmFn(dialogId)
	{
		const sidebarType = resolveSidebarType(dialogId);

		switch (sidebarType)
		{
			case SidebarType.channel:
				return showDeleteChannelAlert;

			case SidebarType.collab:
				return showDeleteCollabAlert;

			default:
				return showDeleteChatAlert;
		}
	}

	/**
	 * @param {DialogId} dialogId
	 * @return {function}
	 */
	function resolveClearHistoryDialogConfirmFn(dialogId)
	{
		const sidebarType = resolveSidebarType(dialogId);

		// eslint-disable-next-line sonarjs/no-small-switch
		switch (sidebarType)
		{
			case SidebarType.collab:
				return showClearHistoryCollabAlert;

			default:
				return showClearHistoryChatAlert;
		}
	}

	/**
	 * @param {string} dialogId
	 * @return {string}
	 */
	function resolveDeleteDialogToastType(dialogId)
	{
		const sidebarType = resolveSidebarType(dialogId);

		switch (sidebarType)
		{
			case SidebarType.channel:
				return ToastType.deleteChannel;

			case SidebarType.collab:
				return ToastType.deleteCollab;

			default:
				return ToastType.deleteChat;
		}
	}

	module.exports = {
		resolveLeaveDialogConfirmFn,
		resolveDeleteDialogConfirmFn,
		resolveDeleteDialogToastType,
		resolveClearHistoryDialogConfirmFn,
	};
});
