/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/pull-handler/notification
 */
jn.define('im/messenger/pull-handler/notification', (require, exports, module) => {

	const { Type } = jn.require('type');
	const { Loc } = jn.require('loc');
	const { PullHandler } = jn.require('im/messenger/pull-handler/base');
	const { Counters } = jn.require('im/messenger/lib/counters');
	const { Logger } = jn.require('im/messenger/lib/logger');
	const { EventType } = jn.require('im/messenger/const');
	const { Notifier } = jn.require('im/messenger/lib/notifier');

	/**
	 * @class NotificationPullHandler
	 */
	class NotificationPullHandler extends PullHandler
	{
		handleNotifyAdd(params, extra, command)
		{
			Logger.info('NotificationPullHandler.handleNotifyAdd', params);

			// auto read for notification, if it is "I like the message" notification for the opened dialog.
			const dialog = PageManager.getNavigator().getVisible();
			const isDialogOpened = dialog && dialog.data && !Type.isUndefined(dialog.data.DIALOG_ID);
			const isLikeNotification = params.settingName === 'im|like' && params.originalTag.startsWith('RATING|IM|');

			if (isDialogOpened && isLikeNotification)
			{
				const message = params.originalTag.split('|');
				const dialogType = message[2];
				const chatId = message[3];
				const dialogId = dialogType === 'P' ? chatId : 'chat' + chatId;

				const isSameDialog = dialogId === dialog.data.DIALOG_ID.toString();
				if (isSameDialog)
				{
					BX.postComponentEvent('chatbackground::task::action', [
						'readNotification',
						'readNotification|' + params.id,
						{
							action: 'Y',
							id: params.id,
						},
					], 'background');

					return;
				}
			}

			Counters.notificationCounter.value = params.counter;
			this.emitMessengerEvent(EventType.notification.reload, params);
			Counters.update();

			const userName = params.userName ? params.userName : '';
			if (extra && extra.server_time_ago <= 5)
			{
				const purifiedNotificationText = ChatMessengerCommon.purifyText(params.text, params.params);

				Notifier.notify({
					dialogId: 'notify',
					title: Loc.getMessage('IMMOBILE_PULL_HANDLER_NOTIFICATION_TITLE'),
					text: (userName ? userName + ': ' : '') + purifiedNotificationText,
					avatar: params.userAvatar ? params.userAvatar : '',
				});
			}
		}

		handleNotifyRead(params, extra, command)
		{
			Logger.info('NotificationPullHandler.handleNotifyRead', params);

			Counters.notificationCounter.value = params.counter;
			Counters.update();
		}

		handleNotifyUnread(params, extra, command)
		{
			Logger.info('NotificationPullHandler.handleNotifyUnread', params);

			Counters.notificationCounter.value = params.counter;
			Counters.update();

			this.emitMessengerEvent(EventType.notification.reload, params);
		}

		handleNotifyConfirm(params, extra, command)
		{
			Logger.info('NotificationPullHandler.handleNotifyConfirm', params);

			Counters.notificationCounter.value = params.counter;
			Counters.update();

			this.emitMessengerEvent(EventType.notification.reload, params);
		}
	}

	module.exports = {
		NotificationPullHandler,
	};
});
