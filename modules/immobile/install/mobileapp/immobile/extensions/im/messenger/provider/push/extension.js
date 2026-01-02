/**
 * @module im/messenger/provider/push
 */
jn.define('im/messenger/provider/push', (require, exports, module) => {
	/* @global ChatDataConverter */
	const { Type } = require('type');
	const { clone } = require('utils/object');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { MessengerParams } = require('im/messenger/lib/params');
	const {
		EventType,
		ComponentCode,
		OpenDialogContextType,
		WaitingEntity,
		NavigationTabId,
	} = require('im/messenger/const');
	const { Feature } = require('im/messenger/lib/feature');
	const { ComponentRequestBroadcaster } = require('im/messenger/lib/component-request-broadcaster');
	const { getLoggerWithContext } = require('im/messenger/lib/logger');
	const logger = getLoggerWithContext('push-handler', 'PushHandler');

	class PushHandler
	{
		constructor()
		{
			this.store = serviceLocator.get('core').getStore();
			this.manager = Application.getNotificationHistory('im_message');

			this.manager.setOnChangeListener(this.handleChange.bind(this));

			this.storedPullEvents = [];
		}

		get className()
		{
			return this.constructor.name;
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

		async getRawPushEvents()
		{
			if (!Feature.isInstantPushEnabled)
			{
				const pushEvents = this.manager.get();
				logger.log('get push sync', pushEvents);

				return pushEvents;
			}

			const pushEvents = await this.manager.getAsync();
			logger.log('get push async', pushEvents);

			return pushEvents;
		}

		/**
		 * @return {Promise<Array<MessengerPushEvent>>}
		 */
		async getPushEventList()
		{
			const list = await this.getRawPushEvents();
			/** @type {Array<MessengerPushEvent>} */
			const eventList = [];

			if (!list || !list.IM_MESS || list.IM_MESS.length <= 0)
			{
				logger.info('getPushEventList: list is empty');

				return eventList;
			}

			logger.info('getPushEventList: parse push messages', list.IM_MESS);

			list.IM_MESS.forEach((push) => {
				if (push?.data?.cmd !== 'message' && push?.data?.cmd !== 'messageChat')
				{
					return;
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
					return;
				}

				const event = {
					module_id: 'im',
					command: push.data.cmd,
					params: ChatDataConverter.preparePushFormat(push.data),
				};

				event.params.userInChat[event.params.chatId] = [MessengerParams.getUserId()];

				event.params.message.text = this.#sanitizeMessageText(senderMessage);
				event.params.message.text = this.#removeAttachmentSuffixFromMessageText(
					event.params.message.text,
					push.attachmentSuffix,
				);

				if (push.senderCut)
				{
					event.params.message.text = event.params.message.text.slice(Math.max(0, push.senderCut));
				}

				if (!event.params.message.textOriginal)
				{
					event.params.message.textOriginal = event.params.message.text;
				}

				eventList.push({
					command: event.command,
					params: event.params,
					extra: push.extra,
				});
			});
			eventList.sort((event1, event2) => {
				return event1.params.message.id - event2.params.message.id;
			});

			return eventList;
		}

		async updateList(needClearHistory = true)
		{
			const eventList = await this.getPushEventList();
			logger.info('updateList: parse push messages', eventList);

			const isDialogOpen = this.store.getters['applicationModel/isSomeDialogOpen'];

			eventList.forEach((event) => { // TODO: delete after transferring the openlines dialog to a new widget
				const storedEvent = clone(event.params);
				if (storedEvent.message.params?.FILE_ID?.length > 0)
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
					this.storedPullEvents = this.storedPullEvents
						.filter((pullEvent) => pullEvent.message.id !== storedEvent.message.id)
					;
					this.storedPullEvents.push(storedEvent);
				}
			});

			if (Feature.isInstantPushEnabled && eventList.length > 0)
			{
				try
				{
					await this.sendEventsToComponents(eventList);
				}
				catch (error)
				{
					logger.error('updateList: an error occurred when broadcasting push messages', eventList, error);
				}
				finally
				{
					logger.warn('updateList: broadcasting messages was completed');
				}
			}

			if (needClearHistory)
			{
				this.clearHistory();
			}

			return eventList;
		}

		/**
		 *
		 * @param {Array<MessengerPushEvent>} eventList
		 * @return {Promise<void>}
		 */
		async sendEventsToComponents(eventList)
		{
			const requestOptionList = [
				{
					toComponent: ComponentCode.imMessenger,
					data: eventList,
					handlerId: WaitingEntity.push.messageHandler.chat,
				},
				{
					toComponent: ComponentCode.imMessenger,
					data: eventList,
					handlerId: WaitingEntity.push.messageHandler.database,
				},
				{
					toComponent: ComponentCode.imCopilotMessenger,
					data: eventList,
					handlerId: WaitingEntity.push.messageHandler.copilot,
				},
				{
					toComponent: ComponentCode.imNavigation,
					data: eventList,
					handlerId: WaitingEntity.push.messageHandler.counter,
				},
			];

			return ComponentRequestBroadcaster.getInstance()
				.send(EventType.push.messageBatch, requestOptionList)
			;
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

			logger.info('executeAction: execute push-notification', push);

			const pushParams = ChatDataConverter.getPushFormat(push);

			if (pushParams.ACTION && pushParams.ACTION.slice(0, 8) === 'IM_MESS_')
			{
				const userId = parseInt(pushParams.ACTION.slice(8), 10);
				if (userId > 0)
				{
					const openDialogParams = {
						dialogId: userId,
						context: OpenDialogContextType.push,
					};

					if (Feature.isInstantPushEnabled)
					{
						openDialogParams.messageId = pushParams.PARAMS.MESSAGE_ID;
					}

					this.#openDialog(openDialogParams);
				}

				return true;
			}

			if (pushParams.ACTION && pushParams.ACTION.slice(0, 8) === 'IM_CHAT_')
			{
				if (MessengerParams.isOpenlinesOperator() && pushParams.CHAT_TYPE === 'L')
				{
					if (Feature.isMessengerV2Enabled)
					{
						const navigationController = serviceLocator.get('navigation-controller');
						navigationController.makeMessengerTabActive();
						navigationController.setActiveTab(NavigationTabId.openlines);

						return false;
					}

					if (!PageManager.getNavigator().isActiveTab())
					{
						PageManager.getNavigator().makeTabActive();
					}

					BX.postComponentEvent('onTabChange', ['openlines'], ComponentCode.imNavigation);

					return false;
				}

				const chatId = parseInt(pushParams.ACTION.slice(8), 10);
				if (chatId === 0)
				{
					return false;
				}

				const openDialogParams = {
					dialogId: `chat${chatId}`,
					context: OpenDialogContextType.push,
				};

				if (Feature.isInstantPushEnabled)
				{
					openDialogParams.messageId = pushParams.PARAMS.MESSAGE_ID;
				}

				BX.postComponentEvent('onTabChange', ['chats'], ComponentCode.imNavigation);
				MessengerEmitter.emit(EventType.messenger.openDialog, openDialogParams, ComponentCode.imMessenger);

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

		/**
		 * @param {string} text
		 * @return {string}
		 */
		#sanitizeMessageText(text)
		{
			return text.toString()
				.replaceAll('&', '&amp;')
				.replaceAll('"', '&quot;')
				.replaceAll('<', '&lt;')
				.replaceAll('>', '&gt;')
			;
		}

		/**
		 * @param {string} text
		 * @param {string} attachmentSuffix
		 * @returns {string}
		 */
		#removeAttachmentSuffixFromMessageText(text, attachmentSuffix)
		{
			return text.replaceAll(attachmentSuffix, '');
		}

		#openDialog(openDialogParams)
		{
			if (Feature.isMessengerV2Enabled)
			{
				serviceLocator.get('dialog-manager').openDialog(openDialogParams)
					.catch((error) => {
						logger.error('openDialog error', error);
					});

				return;
			}
			MessengerEmitter.emit(
				EventType.messenger.openDialog,
				openDialogParams,
				ComponentCode.imMessenger,
			);
		}
	}

	module.exports = {
		PushHandler,
	};
});
