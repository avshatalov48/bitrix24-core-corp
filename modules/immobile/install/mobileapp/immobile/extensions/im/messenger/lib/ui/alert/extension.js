/**
 * @module im/messenger/lib/ui/alert
 */
jn.define('im/messenger/lib/ui/alert', (require, exports, module) => {
	const { confirmDestructiveAction } = require('alert');
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
	};
});
