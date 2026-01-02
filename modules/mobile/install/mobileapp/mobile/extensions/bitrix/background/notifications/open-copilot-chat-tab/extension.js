/**
 * @module background/notifications/open-copilot-chat-tab
 */
jn.define('background/notifications/open-copilot-chat-tab', (require, exports, module) => {
	const {
		BaseNotificationHandler,
	} = require('background/notifications/base');
	const { AnalyticsEvent } = require('analytics');

	/**
	 * @class OpenCopilotChatTabNotification
	 */
	class OpenCopilotChatTabNotification extends BaseNotificationHandler
	{
		getNotificationType()
		{
			return 'MOBILE_OPEN_COPILOT_CHAT';
		}

		handleNotificationClick(message)
		{
			const analytics = this.getAnalytics();
			if (analytics)
			{
				analytics.send();
			}

			// eslint-disable-next-line no-undef
			requireLazyBatch(['im:messenger/api/tab', 'im:messenger/api/navigation'], false)
				.then(async (extensions) => {
					const { openCopilotTab } = extensions.get('im:messenger/api/tab');
					const { closeAll } = extensions.get('im:messenger/api/navigation');

					try
					{
						await closeAll?.();
						await openCopilotTab?.();
					}
					catch (error)
					{
						console.error(error);
					}
				})
				.catch((error) => console.error(error));
		}

		getAnalytics()
		{
			return new AnalyticsEvent()
				.setEvent('push_mobile_1-8d_copilot')
				.setCategory('1-8d')
				.setTool('mobile');
		}

		getNotificationEventName()
		{
			return '';
		}

		getSubscriptionEventName()
		{
			return '';
		}
	}

	module.exports = {
		OpenCopilotChatTabNotification,
	};
});
