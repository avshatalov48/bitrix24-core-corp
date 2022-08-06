/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/push-handler
 */
jn.define('im/messenger/push-handler', (require, exports, module) => {

	const { Type } = jn.require('type');
	const { Logger } = jn.require('im/messenger/lib/logger');
	const { MessengerEvent } = jn.require('im/messenger/lib/event');
	const { MessengerParams } = jn.require('im/messenger/lib/params');
	const { EventType } = jn.require('im/messenger/const');

	class PushHandler
	{
		constructor()
		{
			this.manager = Application.getNotificationHistory('im_message');

			this.manager.setOnChangeListener(this.handleChange.bind(this));

			this.storedPullEvents = [];
		}

		getStoredPullEvents()
		{
			const list = [].concat(this.storedPullEvents);

			this.storedPullEvents = [];

			return list;
		}

		handleChange()
		{
			BX.onViewLoaded(() => {
				this.updateList();
			});
		}

		updateList()
		{
			const list = this.manager.get();
			if (!list || !list['IM_MESS'] || list['IM_MESS'].length <= 0)
			{
				Logger.info('PushHandler.updateList: list is empty');

				return true;
			}

			Logger.info('PushHandler.updateList: parse push messages', list['IM_MESS']);

			const isDialogOpen = MessengerStore.getters['applicationModel/isDialogOpen'];

			list['IM_MESS'].forEach((push) => {
				if (!push.data)
				{
					return false;
				}

				if (!(push.data.cmd === 'message' || push.data.cmd === 'messageChat'))
				{
					return false;
				}

				let senderMessage = '';
				if (!Type.isUndefined(push.senderMessage))
				{
					senderMessage = push.senderMessage;
				}
				else if (!Type.isUndefined(push.aps) && push.aps.alert.body)
				{
					senderMessage = push.aps.alert.body;
				}

				if (!senderMessage)
				{
					return false;
				}

				const event = {
					module_id: 'im',
					command: push.data.cmd,
					params: ChatDataConverter.preparePushFormat(push.data)
				};

				event.params.userInChat[event.params.chatId] = [MessengerParams.getUserId()];

				event.params.message.text  = senderMessage.toString().replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

				if (push.senderCut)
				{
					event.params.message.text = event.params.message.text.substring(push.senderCut)
				}

				if (!event.params.message.textOriginal)
				{
					event.params.message.textOriginal = event.params.message.text;
				}

				const storedEvent = ChatUtils.objectClone(event.params);
				if (storedEvent.message.params.FILE_ID && storedEvent.message.params.FILE_ID.length > 0)
				{
					storedEvent.message.text = '';
					storedEvent.message.textOriginal = '';
				}

				if (isDialogOpen)
				{
					BX.postWebEvent('chatrecent::push::get', storedEvent)
				}
				else
				{
					this.storedPullEvents = this.storedPullEvents.filter(event => event.message.id !== storedEvent.message.id);
					this.storedPullEvents.push(storedEvent);
				}

				const recentItem = MessengerStore.getters['recentModel/getById'](event.params.dialogId);
				if (!recentItem || recentItem.message.id < event.params.message.id)
				{
					BX.PULL.emit({
						type: 'server',
						moduleId: event.module_id,
						data: {
							command: event.command,
							params: event.params,
							extra: push.extra,
						},
					});
				}
			});

			this.manager.clear();

			return true;
		}

		executeAction()
		{
			if (Application.isBackground())
			{
				return false;
			}

			const push = Application.getLastNotification();
			if (Type.isPlainObject(push) && Object.keys(push).length === 0)
			{
				return false;
			}

			Logger.info('PushHandler.executeAction: execute push-notification', push);

			const pushParams = ChatDataConverter.getPushFormat(push);

			if (pushParams.ACTION && pushParams.ACTION.substring(0, 8) === 'IM_MESS_')
			{
				const userId = parseInt(pushParams.ACTION.substring(8));
				if (userId > 0)
				{
					new MessengerEvent(EventType.messenger.openDialog, { dialogId: userId }).send();
				}

				return;
			}

			if (pushParams.ACTION && pushParams.ACTION.substring(0, 8) === 'IM_CHAT_')
			{
				if (MessengerParams.isOpenlinesOperator() && pushParams.CHAT_TYPE === 'L')
				{
					if (!PageManager.getNavigator().isActiveTab())
					{
						PageManager.getNavigator().makeTabActive();
					}

					BX.postComponentEvent('onTabChange', ['openlines'], 'im.navigation');

					return false;
				}

				const chatId = parseInt(pushParams.ACTION.substring(8));
				if (chatId > 0)
				{
					new MessengerEvent(EventType.messenger.openDialog, { dialogId: 'chat' + chatId }).send();
				}

				return;
			}

			if (pushParams.ACTION && pushParams.ACTION === 'IM_NOTIFY')
			{
				new MessengerEvent(EventType.messenger.openNotifications).send();
			}

			return true;
		}

		clearHistory()
		{
			this.manager.clear();
		}
	}

	module.exports = {
		PushHandler: new PushHandler(),
	};
});
