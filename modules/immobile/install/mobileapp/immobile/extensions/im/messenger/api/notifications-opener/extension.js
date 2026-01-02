/**
 * @module im/messenger/api/notifications-opener
 */
jn.define('im/messenger/api/notifications-opener', (require, exports, module) => {
	const { Loc } = require('im/messenger/loc');

	function getTopMenuNotificationsButton(parentWidget)
	{
		return {
			id: 'notification',
			testId: 'notification_badge',
			type: 'notification',
			badgeCode: 'notifications',
			callback: () => showNotificationList(parentWidget),
		};
	}

	function showNotificationList(parentWidget = PageManager)
	{
		parentWidget.openComponent(
			'JSStackComponent',
			{
				componentCode: 'im.notify.legacy',
				// eslint-disable-next-line no-undef
				scriptPath: availableComponents['im:im.notify.legacy'].publicUrl,
				canOpenInDefault: true,
				rootWidget: {
					name: 'web',
					settings: {
						page: {
							url: `${env.siteDir}mobile/im/notify.php?navigation`,
							preload: false,
						},
						titleParams: {
							useLargeTitleMode: true,
						},
						objectName: 'widget',
						swipeToClose: true,
					},
				},
				params: {
					MESSAGES: {
						COMPONENT_TITLE: Loc.getMessage('IMMOBILE_NOTIFICATIONS_COMPONENT_TITLE'),
					},
				},
			},
		);
	}

	module.exports = {
		showNotificationList,
		getTopMenuNotificationsButton,
	};
});
