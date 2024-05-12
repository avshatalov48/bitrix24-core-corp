/**
 * @module im/messenger/lib/ui/notification
 */
jn.define('im/messenger/lib/ui/notification', (require, exports, module) => {
	include('InAppNotifier');

	const { Loc } = require('loc');

	/**
	 * @class Notify
	 */
	class Notification
	{
		static showComingSoon()
		{
			InAppNotifier.showNotification({
				title: Loc.getMessage('IMMOBILE_MESSENGER_UI_NOTIFY_COMING_SOON'),
				time: 1,
				backgroundColor: '#E6000000',
			});
		}
	}

	module.exports = { Notification };
});
