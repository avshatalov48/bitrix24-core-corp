/**
 * @module im/messenger/controller/recent/chat/recent
 */
jn.define('im/messenger/controller/recent/chat/recent', (require, exports, module) => {
	const { clone } = require('utils/object');
	const { Counters } = require('im/messenger/lib/counters');
	const { Calls } = require('im/messenger/lib/integration/immobile/calls');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { BaseRecent } = require('im/messenger/controller/recent/lib');
	const { RecentConverter } = require('im/messenger/lib/converter');
	const { EventType, ComponentCode } = require('im/messenger/const');
	const { DialogRest } = require('im/messenger/provider/rest');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { ShareDialogCache } = require('im/messenger/cache/share-dialog');
	const logger = LoggerManager.getInstance().getLogger('recent--chat-recent');

	/**
	 * @class ChatRecent
	 */
	class ChatRecent extends BaseRecent
	{
		constructor(options = {})
		{
			super({ ...options, logger });
		}

		bindMethods()
		{
			super.bindMethods();

			this.commentCountersDeleteHandler = this.commentCountersDeleteHandler.bind(this);
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
			;
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
					this.joinCall(recentItem.params.call.id);
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
			this.store.dispatch('dialoguesModel/clearAllCounters')
				.then(() => {
					return this.store.dispatch('recentModel/clearAllCounters');
				})
				.then(() => {
					this.renderer.render();

					Counters.update();

					return DialogRest.readAllMessages();
				})
				.then((result) => {
					this.logger.info(`${this.constructor.name}.readAllMessages result:`, result);
				})
				.catch((error) => {
					this.logger.error(`${this.constructor.name}.readAllMessages catch:`, error);
				})
			;
		}

		joinCall(callId)
		{
			Calls.joinCall(callId);
		}

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

			const callItem = RecentConverter.toCallItem(status, call);

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
			this.view.findItem({ id: callItem.id }, (item) => {
				if (item)
				{
					this.view.updateItem({ id: callItem.id }, callItem);

					return;
				}

				this.view.addItems([callItem]);
			});
		}

		removeCallById(id)
		{
			this.view.removeItem({ id: `call${id}` });
		}

		/* endregion Events */

		commentCountersDeleteHandler(mutation)
		{
			const { channelId } = mutation.payload.data;
			const dialog = this.store.getters['dialoguesModel/getByChatId'](channelId);
			const recentItem = clone(this.store.getters['recentModel/getById'](dialog.dialogId));

			if (recentItem)
			{
				this.updateItems([recentItem]);
				Counters.update();
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
		 * @param {Array<object>} recentItems
		 * @return {Promise<any>}
		 */
		async saveRecentData(recentItems)
		{
			const modelData = this.prepareDataForModels(recentItems);

			const usersPromise = await this.store.dispatch('usersModel/set', modelData.users);
			const dialoguesPromise = await this.store.dispatch('dialoguesModel/set', modelData.dialogues);
			const recentPromise = await this.store.dispatch('recentModel/set', modelData.recent);

			if (this.recentService.pageNavigation.currentPage === 1)
			{
				const recentIndex = [];
				modelData.recent.forEach((item) => recentIndex.push(item.id.toString()));

				const idListForDeleteFromCache = [];
				this.store.getters['recentModel/getCollection']()
					.forEach((item) => {
						if (!recentIndex.includes(item.id.toString()))
						{
							idListForDeleteFromCache.push(item.id);
						}
					});

				idListForDeleteFromCache.forEach((id) => {
					this.store.dispatch('recentModel/deleteFromModel', { id });
				});

				await this.saveShareDialogCache(modelData.recent);
			}

			return Promise.all([usersPromise, dialoguesPromise, recentPromise]);
		}

		/**
		 * @param {Array} recentItems
		 * @return {Promise}
		 */
		saveShareDialogCache(recentItems)
		{
			return ShareDialogCache.saveRecentItemList(recentItems);
		}
	}

	module.exports = { ChatRecent };
});
