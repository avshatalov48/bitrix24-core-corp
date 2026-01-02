/**
 * @module im/messenger/controller/recent/chat/recent
 */
jn.define('im/messenger/controller/recent/chat/recent', (require, exports, module) => {
	const { Type } = require('type');
	const { clone } = require('utils/object');
	const { CallManager } = require('im/messenger/lib/integration/callmobile/call-manager');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { BaseRecent } = require('im/messenger/controller/recent/lib');
	const { RecentUiConverter } = require('im/messenger/lib/converter/ui/recent');
	const { EventType, ComponentCode } = require('im/messenger/const');
	const { DialogRest } = require('im/messenger/provider/rest');
	const { ShareDialogCache } = require('im/messenger/cache/share-dialog');
	const { MessengerCounterSender } = require('im/messenger/lib/counters/counter-manager/messenger/sender');
	const { readAllCountersOnClient } = require('im/messenger/lib/counters/counter-manager/messenger/actions');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { getLogger } = require('im/messenger/lib/logger');
	const logger = getLogger('recent--chat-recent');

	/**
	 * @class ChatRecent
	 */
	class ChatRecent extends BaseRecent
	{
		constructor(options = {})
		{
			super({ ...options, logger });

			this.initShareDialogCache();
		}

		bindMethods()
		{
			super.bindMethods();

			this.commentCountersDeleteHandler = this.commentCountersDeleteHandler.bind(this);
			this.setCommentsHandler = this.setCommentsHandler.bind(this);
			this.departmentColleaguesGetHandler = this.departmentColleaguesGetHandler.bind(this);
		}

		subscribeViewEvents()
		{
			super.subscribeViewEvents();

			this.view
				.on(EventType.recent.itemSelected, this.onItemSelected.bind(this))
				.on(EventType.recent.searchShow, this.onShowSearchDialog.bind(this))
				.on(EventType.recent.searchHide, this.onHideSearchDialog.bind(this))
				.on(EventType.recent.createChat, this.onCreateChat.bind(this))
				.on(EventType.recent.readAll, this.onReadAll.bind(this))
			;
		}

		subscribeStoreEvents()
		{
			super.subscribeStoreEvents();

			this.storeManager
				.on('commentModel/deleteChannelCounters', this.commentCountersDeleteHandler)
				.on('commentModel/setCounters', this.setCommentsHandler)
			;
		}

		initShareDialogCache()
		{
			this.shareDialogCache = new ShareDialogCache();
		}

		/* region Events */

		onItemSelected(recentItem)
		{
			if (recentItem.params.disableTap)
			{
				return;
			}

			if (recentItem.params.type === 'call')
			{
				if (recentItem.params.canJoin)
				{
					this.joinCall(
						recentItem.params.call.id,
						recentItem.params.call.uuid,
						recentItem.params.call.associatedEntity,
					);
				}
				else
				{
					this.openDialog(recentItem.params.call.associatedEntity.id, ComponentCode.imMessenger);
				}

				return;
			}

			this.openDialog(recentItem.id, ComponentCode.imMessenger);
		}

		onShowSearchDialog()
		{
			MessengerEmitter.emit(EventType.messenger.showSearch, {}, ComponentCode.imMessenger);
		}

		onHideSearchDialog()
		{
			MessengerEmitter.emit(EventType.messenger.hideSearch, {}, ComponentCode.imMessenger);
		}

		onCreateChat()
		{
			MessengerEmitter.emit(EventType.messenger.createChat, {}, ComponentCode.imMessenger);
		}

		onReadAll()
		{
			readAllCountersOnClient()
				.then(() => DialogRest.readAllMessages())
				.then((result) => {
					this.logger.info(`${this.constructor.name}.readAllMessages result:`, result);
				})
				.catch((error) => {
					this.logger.error(`${this.constructor.name}.readAllMessages catch:`, error);
				})
			;
		}

		joinCall(callId, callUuid, associatedEntity)
		{
			CallManager.getInstance().joinCall(callId, callUuid, associatedEntity);
		}

		/**
		 * @param {RecentCallData} call
		 * @param {RecentCallStatus} callStatus
		 */
		addCall(call, callStatus)
		{
			let status = callStatus;
			if (
				call.associatedEntity.advanced.entityType === 'VIDEOCONF'
				&& call.associatedEntity.advanced.entityData1 === 'BROADCAST'
			)
			{
				status = 'remote';
			}

			const callItem = RecentUiConverter.toCallItem(status, call);

			this.saveCall(callItem);
			this.drawCall(callItem);
		}

		saveCall(call)
		{
			const elementIndex = this.callList.findIndex((element) => element.id === call.id);
			if (elementIndex >= 0)
			{
				this.callList[elementIndex] = call;

				return;
			}

			this.callList.push(call);
		}

		getCallById(callId)
		{
			return this.callList.find((call) => call.id === callId);
		}

		drawCall(callItem)
		{
			this.upsert(callItem, false);
		}

		removeCall(call)
		{
			this.removeItem(call, true);
		}

		/* endregion Events */

		commentCountersDeleteHandler(mutation)
		{
			const { channelId } = mutation.payload.data;
			const dialog = this.store.getters['dialoguesModel/getByChatId'](channelId);
			if (!dialog)
			{
				return;
			}

			const recentItem = clone(this.store.getters['recentModel/getById'](dialog.dialogId));
			if (recentItem)
			{
				this.updateItems([recentItem]);
				serviceLocator.get('tab-counters').update();
			}
		}

		/**
		 * @param {MutationPayload<CommentsSetCountersData, CommentsSetCountersActions>} mutation.payload
		 */
		setCommentsHandler(mutation)
		{
			const { data, actionName } = mutation.payload;

			if (actionName !== 'clearAllCounters')
			{
				return;
			}

			const { affectedChannels } = data;

			if (!Type.isArrayFilled(affectedChannels))
			{
				return;
			}
			const recentItemList = [];

			affectedChannels.forEach((channelId) => {
				const dialog = this.store.getters['dialoguesModel/getByChatId'](channelId);
				if (!dialog)
				{
					return;
				}

				const recentItem = this.store.getters['recentModel/getById'](dialog.dialogId);
				if (recentItem)
				{
					recentItemList.push(clone(recentItem));
				}
			});

			if (Type.isArrayFilled(recentItemList))
			{
				this.updateItems(recentItemList);
				serviceLocator.get('tab-counters').update();
			}
		}

		initMessengerHandler(data)
		{
			super.initMessengerHandler(data);

			if (data?.departmentColleagues)
			{
				this.departmentColleaguesGetHandler(data.departmentColleagues);
			}
		}

		departmentColleaguesGetHandler(userList)
		{
			this.logger.log(`${this.constructor.name}.departmentColleaguesGetHandler`, userList);

			this.store.dispatch('usersModel/set', userList)
				.catch((err) => {
					this.logger.error(`${this.constructor.name}.departmentColleaguesGetHandler.usersModel/set.catch:`, err);
				});
		}

		/**
		 * @param {Array<object>} recentData
		 * @return {Promise<any>}
		 */
		async saveRecentData(recentData)
		{
			const modelData = this.prepareDataForModels(recentData);

			void await this.store.dispatch('usersModel/set', modelData.users);
			void await this.store.dispatch('dialoguesModel/set', modelData.dialogues);
			void await this.store.dispatch('dialoguesModel/copilotModel/setCollection', modelData.copilot);
			void await this.store.dispatch('recentModel/set', modelData.recent);
			if (this.recentService.pageNavigation.currentPage === 2)
			{
				this.deleteItemsFromStore(modelData.recent);

				await this.saveShareDialogCache();
			}
			MessengerCounterSender.getInstance().sendRecentPageLoaded(modelData.counterState);
		}

		/**
		 * @return {Promise}
		 */
		saveShareDialogCache()
		{
			return this.shareDialogCache.saveRecentItemListThrottled();
		}
	}

	module.exports = { ChatRecent };
});
