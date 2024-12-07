/**
 * @module im/messenger/provider/push
 */
jn.define('im/messenger/provider/push', (require, exports, module) => {
	const { Type } = require('type');
	const { clone } = require('utils/object');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { MessengerParams } = require('im/messenger/lib/params');
	const {
		EventType,
		DialogType,
		ComponentCode,
		OpenDialogContextType,
	} = require('im/messenger/const');
	const { EntityReady } = require('entity-ready');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('push-handler');

	class PushHandler
	{
		constructor()
		{
			this.store = serviceLocator.get('core').getStore();
			this.manager = Application.getNotificationHistory('im_message');

			this.manager.setOnChangeListener(this.handleChange.bind(this));

			this.storedPullEvents = [];
		}

		getStoredPullEvents()
		{
			const list = [...this.storedPullEvents];

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

			if (!list || !list.IM_MESS || list.IM_MESS.length <= 0)
			{
				logger.info('PushHandler.updateList: list is empty');

				return true;
			}

			logger.info('PushHandler.updateList: parse push messages', list.IM_MESS);

			const isDialogOpen = this.store.getters['applicationModel/isSomeDialogOpen'];

			list.IM_MESS.forEach((push) => {
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
					params: ChatDataConverter.preparePushFormat(push.data),
				};

				event.params.userInChat[event.params.chatId] = [MessengerParams.getUserId()];

				event.params.message.text = senderMessage.toString()
					.replace(/&/g, '&amp;')
					.replace(/"/g, '&quot;')
					.replace(/</g, '&lt;')
					.replace(/>/g, '&gt;')
				;

				if (push.senderCut)
				{
					event.params.message.text = event.params.message.text.slice(Math.max(0, push.senderCut));
				}

				if (!event.params.message.textOriginal)
				{
					event.params.message.textOriginal = event.params.message.text;
				}

				const storedEvent = clone(event.params);
				if (storedEvent.message.params.FILE_ID && storedEvent.message.params.FILE_ID.length > 0)
				{
					storedEvent.message.text = '';
					storedEvent.message.textOriginal = '';
				}

				if (isDialogOpen)
				{
					BX.postWebEvent('chatrecent::push::get', storedEvent);
				}
				else
				{
					this.storedPullEvents = this.storedPullEvents.filter((event) => event.message.id !== storedEvent.message.id);
					this.storedPullEvents.push(storedEvent);
				}

				const recentItem = this.store.getters['recentModel/getById'](event.params.dialogId);
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

		async executeAction()
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

			logger.info('PushHandler.executeAction: execute push-notification', push);

			const pushParams = ChatDataConverter.getPushFormat(push);

			if (pushParams.ACTION && pushParams.ACTION.slice(0, 8) === 'IM_MESS_')
			{
				const userId = parseInt(pushParams.ACTION.slice(8), 10);
				if (userId > 0)
				{
					MessengerEmitter.emit(EventType.messenger.openDialog, {
						dialogId: userId,
						context: OpenDialogContextType.push,
					}, ComponentCode.imMessenger);
				}

				return true;
			}

			if (pushParams.ACTION && pushParams.ACTION.slice(0, 8) === 'IM_CHAT_')
			{
				if (MessengerParams.isOpenlinesOperator() && pushParams.CHAT_TYPE === 'L')
				{
					if (!PageManager.getNavigator().isActiveTab())
					{
						PageManager.getNavigator().makeTabActive();
					}

					BX.postComponentEvent('onTabChange', ['openlines'], ComponentCode.imNavigation);

					return false;
				}

				const chatId = parseInt(pushParams.ACTION.slice(8), 10);
				if (chatId > 0)
				{
					let componentCode = ComponentCode.imMessenger;
					if (push.data[1] && push.data[1][13] && push.data[1][13] === DialogType.copilot)
					{
						componentCode = ComponentCode.imCopilotMessenger;

						await EntityReady.wait('copilot-messenger');
						BX.postComponentEvent('onTabChange', ['copilot'], ComponentCode.imNavigation);
					}
					else
					{
						BX.postComponentEvent('onTabChange', ['chats'], ComponentCode.imNavigation);
					}

					MessengerEmitter.emit(
						EventType.messenger.openDialog,
						{ dialogId: `chat${chatId}`, context: OpenDialogContextType.push },
						componentCode,
					);
				}

				return true;
			}

			if (pushParams.ACTION && pushParams.ACTION === 'IM_NOTIFY')
			{
				MessengerEmitter.emit(EventType.messenger.openNotifications, {}, ComponentCode.imMessenger);
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
