/**
 * @module im/messenger/lib/ui/alert
 */
jn.define('im/messenger/lib/ui/alert', (require, exports, module) => {
	const { confirmDestructiveAction, confirmDefaultAction } = require('alert');
	const { Loc } = require('loc');

	/**
	 * @param {function} deleteCallback
	 * @param {function} cancelCallback
	 */
	function showDeleteChannelAlert({ deleteCallback, cancelCallback })
	{
		confirmDestructiveAction({
			title: Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_ALERT_DELETE_CHANNEL_TITLE'),
			description: Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_ALERT_DELETE_CHANNEL_DESCRIPTION'),
			onDestruct: deleteCallback,
			onCancel: cancelCallback,
		});
	}

	/**
	 * @param {function} deleteCallback
	 * @param {function} cancelCallback
	 */
	function showDeleteChatAlert({ deleteCallback, cancelCallback })
	{
		confirmDestructiveAction({
			title: Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_ALERT_DELETE_CHAT_TITLE'),
			description: Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_ALERT_DELETE_CHAT_DESCRIPTION'),
			onDestruct: deleteCallback,
			onCancel: cancelCallback,
		});
	}

	/**
	 * @param {function} deleteCallback
	 * @param {function} cancelCallback
	 */
	function showDeleteCollabAlert({ deleteCallback, cancelCallback })
	{
		confirmDestructiveAction({
			title: Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_ALERT_DELETE_COLLAB_TITLE'),
			description: Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_ALERT_DELETE_COLLAB_DESCRIPTION'),
			actionButtonText: Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_ALERT_DELETE_COLLAB_CONFIRM_YES'),
			onDestruct: deleteCallback,
			onCancel: cancelCallback,
		});
	}

	/**
	 * @param {function} leaveCallback
	 * @param {function} cancelCallback
	 */
	function showLeaveCollabAlert({ leaveCallback, cancelCallback })
	{
		confirmDefaultAction({
			title: Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_ALERT_LEAVE_COLLAB_TITLE'),
			description: Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_ALERT_LEAVE_COLLAB_DESCRIPTION'),
			actionButtonText: Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_ALERT_LEAVE_COLLAB_CONFIRM_YES'),
			onAction: leaveCallback,
			onCancel: cancelCallback,
		});
	}

	/**
	 * @param {function} leaveCallback
	 * @param {function} cancelCallback
	 */
	function showLeaveChannelAlert({ leaveCallback, cancelCallback })
	{
		confirmDefaultAction({
			title: Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_ALERT_LEAVE_CHANNEL_CONFIRM_TITLE'),
			actionButtonText: Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_ALERT_LEAVE_CHANNEL_CONFIRM_YES'),
			onAction: leaveCallback,
			onCancel: cancelCallback,
		});
	}

	/**
	 * @param {function} leaveCallback
	 * @param {function} cancelCallback
	 */
	function showLeaveChatAlert({ leaveCallback, cancelCallback })
	{
		confirmDefaultAction({
			title: Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_ALERT_LEAVE_CHAT_CONFIRM_TITLE'),
			actionButtonText: Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_ALERT_LEAVE_CHAT_CONFIRM_YES'),
			onAction: leaveCallback,
			onCancel: cancelCallback,
		});
	}

	/**
	 * @param {function} removeCallback
	 * @param {function} cancelCallback
	 */
	function showRemoveParticipantCollabAlert({ removeCallback, cancelCallback })
	{
		confirmDefaultAction({
			title: Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_ALERT_REMOVE_COLLAB_TITLE'),
			description: Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_ALERT_REMOVE_COLLAB_DESCRIPTION'),
			actionButtonText: Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_ALERT_REMOVE_COLLAB_CONFIRM_YES'),
			onAction: removeCallback,
			onCancel: cancelCallback,
		});
	}

	/**
	 * @param {function} deleteCallback
	 * @param {function} cancelCallback
	 */
	function showDeleteChannelPostAlert({ deleteCallback, cancelCallback })
	{
		confirmDestructiveAction({
			title: Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_ALERT_DELETE_CHANNEL_POST_TITLE'),
			description: Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_ALERT_DELETE_CHANNEL_POST_DESCRIPTION'),
			onDestruct: deleteCallback,
			onCancel: cancelCallback,
		});
	}

	module.exports = {
		showDeleteChatAlert,
		showDeleteChannelAlert,
		showDeleteChannelPostAlert,
		showRemoveParticipantCollabAlert,
		showDeleteCollabAlert,
		showLeaveCollabAlert,
		showLeaveChatAlert,
		showLeaveChannelAlert,
	};
});
