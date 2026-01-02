/**
 * @module im/messenger-v2/controller/recent/service/vuex/chat
 */
jn.define('im/messenger-v2/controller/recent/service/vuex/chat', (require, exports, module) => {
	const { Type } = require('type');
	const { unique } = require('utils/array');
	const {
		NavigationTabId,
		CounterType,
	} = require('im/messenger/const');
	const { RecentEventType } = require('im/messenger-v2/controller/recent/const');
	const { BaseRecentService } = require('im/messenger-v2/controller/recent/service/base');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { AnchorMutationHandler } = require('im/messenger-v2/controller/recent/service/vuex/lib/handlers/anchor');

	/**
	 * @implements {IVuexService}
	 * @class ChatVuexService
	 */
	class ChatVuexService extends BaseRecentService
	{
		onInit()
		{
			this.logger.log('onInit');

			this.anchor = new AnchorMutationHandler(this.recentLocator, this.logger);
			this.#subscribeStoreMutation();
		}

		/**
		 * @return {MessengerCoreStoreManager}
		 */
		get storeManager()
		{
			return serviceLocator.get('core').getStoreManager();
		}

		#subscribeStoreMutation()
		{
			this.storeManager
				.on('recentModel/add', this.recentAddHandler)
				.on('recentModel/update', this.recentUpdateHandler)
				.on('recentModel/delete', this.recentDeleteHandler)
				.on('recentModel/storeIdCollection', this.recentFirstPageHandler)
				.on('recentModel/deleteFromChatIdCollection', this.recentDeleteFromIdCollectionHandler)
				.on('dialoguesModel/add', this.dialogUpdateHandler)
				.on('dialoguesModel/update', this.dialogUpdateHandler)
				.on('dialoguesModel/clearAllCounters', this.dialogReadAllCountersHandler)
				.on('counterModel/set', this.counterSetHandler)
				.on('counterModel/delete', this.counterDeleteHandler)
				.on('anchorModel/add', this.anchor.addHandler)
				.on('anchorModel/delete', this.anchor.deleteHandler)
				.on('anchorModel/deleteMany', this.anchor.deleteManyHandler)
			;
		}

		/**
		 * @param {MutationPayload<RecentAddData|RecentV2UpdateData>} payload
		 * @return {boolean}
		 */
		#isFirstPageByTabAction(payload)
		{
			return payload?.actionName === 'setFirstPageByTab';
		}

		/**
		 * @param {Array<{fields: Partial<RecentModelState>}>} items
		 * @return {Array<RecentModelState>}
		 */
		#getFilteredRecentItems(items)
		{
			const filteredFieldsItem = this.#filterByChatCollection(items, (item) => item.fields?.id);

			return filteredFieldsItem
				.map((fieldItem) => this.storeManager.store.getters['recentModel/getById'](fieldItem.fields.id))
				.filter(Boolean);
		}

		/**
		 * @param {MutationPayload<RecentAddData|RecentV2UpdateData>} payload
		 * @return {Array<RecentModelState>|null}
		 */
		#validateAndGetRecentItems(payload)
		{
			if (this.#isFirstPageByTabAction(payload))
			{
				this.logger.log('validateAndGetRecentItems: skip by setFirstPageByTab action');

				return null;
			}

			const recentItemList = payload?.data?.recentItemList;
			if (!Type.isArray(recentItemList) || recentItemList.length === 0)
			{
				this.logger.warn('validateAndGetRecentItems:recentItemList is empty or invalid', recentItemList);

				return null;
			}

			const recentItems = this.#getFilteredRecentItems(recentItemList);
			if (recentItems.length === 0)
			{
				this.logger.log('validateAndGetRecentItems: no chat items found after filtering');

				return null;
			}

			return recentItems;
		}

		/**
		 * @param {MutationPayload<RecentAddData>} payload
		 * @void
		 */
		recentAddHandler = ({ payload }) => {
			this.logger.log('recentAddHandler', payload);

			const recentItems = this.#validateAndGetRecentItems(payload);
			if (!recentItems)
			{
				return;
			}

			this.#updateItems(recentItems);
		};

		/**
		 * @param {MutationPayload<RecentV2UpdateData>} payload
		 * @void
		 */
		recentUpdateHandler = ({ payload }) => {
			this.logger.log('recentUpdateHandler', payload);

			const recentItems = this.#validateAndGetRecentItems(payload);
			if (!recentItems)
			{
				return;
			}

			this.#updateItems(recentItems);
		};

		/**
		 * @param {MutationPayload<RecentV2StoreIdCollectionData>} payload
		 * @void
		 */
		recentFirstPageHandler = ({ payload }) => {
			this.logger.log('recentFirstPageHandler', payload);

			if (payload?.data.tab !== NavigationTabId.chats)
			{
				this.logger.log('recentFirstPageHandler: tab is not chats, skipping');

				return;
			}

			const firstPageItems = this.storeManager.store.getters['recentModel/getChatFirstPage']();
			if (firstPageItems.length === 0)
			{
				this.logger.log('recentFirstPageHandler: firstPageItems is empty');
			}

			this.recentLocator.get('render').setItems(firstPageItems);
			this.recentLocator.get('render').renderInstant();
			this.recentLocator.get('emitter').emit(RecentEventType.render.updateUIByRecentCollectionSizeIfNeeded, []);
		};

		/**
		 * @param {MutationPayload<RecentV2DeleteData>} payload
		 * @void
		 */
		recentDeleteHandler = ({ payload }) => {
			this.logger.log('recentDeleteHandler', payload);
			const itemId = payload?.data?.id;
			if (!itemId)
			{
				this.logger.log('recentDeleteHandler: recent id is invalid:', payload);

				return;
			}

			if (!this.recentLocator.has('render'))
			{
				this.logger.log('recentDeleteHandler: render service is not defined, skipping', itemId);

				return;
			}

			if (!this.recentLocator.get('render').hasItemRendered(itemId))
			{
				this.logger.log('recentDeleteHandler: deleting an item not from the Chat tab, skipping', itemId);

				return;
			}

			this.recentLocator.get('render').deleteItems([{ id: itemId }]);
			this.recentLocator.get('emitter').emit(RecentEventType.render.updateUIByRecentCollectionSizeIfNeeded, []);
		};

		/**
		 * @param {MutationPayload<RecentV2DeleteData>} payload
		 */
		recentDeleteFromIdCollectionHandler = ({ payload }) => {
			this.logger.log('recentDeleteFromIdCollectionHandler', payload);
			if (payload.actionName !== 'hideByNavigationTabs')
			{
				return;
			}
			const itemId = payload?.data?.id;
			if (!itemId)
			{
				this.logger.log('recentDeleteFromIdCollectionHandler: recent id is invalid:', payload);

				return;
			}

			if (!this.recentLocator.has('render'))
			{
				this.logger.log('recentDeleteFromIdCollectionHandler: render service is not defined, skipping', itemId);

				return;
			}

			if (!this.recentLocator.get('render').hasItemRendered(itemId))
			{
				this.logger.log('recentDeleteFromIdCollectionHandler: deleting an item not from the Chat tab, skipping', itemId);

				return;
			}

			this.recentLocator.get('render').deleteItems([{ id: itemId }]);
			this.recentLocator.get('emitter').emit(RecentEventType.render.updateUIByRecentCollectionSizeIfNeeded, []);
		};

		/**
		 * @param {MutationPayload<DialoguesUpdateData|DialoguesAddData>} payload
		 */
		dialogUpdateHandler = ({ payload }) => {
			this.logger.log('dialogUpdateHandler', payload);
			const dialogId = payload.data.dialogId;

			const isChatItems = this.#filterByChatCollection([{ id: dialogId }], (item) => item.id);
			if (!Type.isArrayFilled(isChatItems))
			{
				this.logger.warn('dialogUpdateHandler: dialog updating an item not from the Chat tab, skipping');

				return;
			}

			const recentItem = this.storeManager.store.getters['recentModel/getById'](String(dialogId));
			if (recentItem)
			{
				this.#updateItems([recentItem]);
			}
		};

		/**
		 * @param {MutationPayload<CounterSetData, CounterSetActions>} payload
		 */
		counterSetHandler = ({ payload }) => {
			this.logger.log('counterSetHandler', payload);
			const { counterList } = payload.data;
			const chatIdList = this.#extractChatIdFromCounterStates(counterList);
			const recentItems = this.#getRecentItemsByChatIdList(chatIdList);
			if (!Type.isArrayFilled(recentItems))
			{
				this.logger.log('counterSetHandler recent items not found. skip mutation', payload);

				return;
			}

			const recentItemsToUpdate = this.#filterByChatCollection(recentItems, (item) => item.id);
			if (!Type.isArrayFilled(recentItemsToUpdate))
			{
				return;
			}

			this.#updateItems(recentItemsToUpdate);
		};

		/**
		 * @param {MutationPayload<CounterDeleteData, CounterDeleteActions>} payload
		 */
		counterDeleteHandler = ({ payload }) => {
			if (payload.actionName !== 'clear')
			{
				return;
			}
			this.logger.log('counterDeleteHandler', payload);

			const { chatIdList } = payload.data;

			const recentItems = this.#getRecentItemsByChatIdList(chatIdList);
			if (!Type.isArrayFilled(recentItems))
			{
				return;
			}

			const recentItemsToUpdate = this.#filterByChatCollection(recentItems, (item) => item.id);
			if (!Type.isArrayFilled(recentItemsToUpdate))
			{
				return;
			}

			this.#updateItems(recentItemsToUpdate);
		};

		/**
		 * @param {MutationPayload<DialoguesClearAllCountersData, DialoguesClearAllCountersActions>} payload
		 */
		dialogReadAllCountersHandler({ payload })
		{
			this.logger.log('dialogReadAllCountersHandler', payload);
			const dialogIdList = payload.data.affectedDialogs;

			if (!Type.isArrayFilled(dialogIdList))
			{
				this.logger.warn('dialogReadAllCountersHandler: affectedDialogs is empty, skipping');

				return;
			}

			const recentIdList = dialogIdList.map((id) => ({ id }));
			const chatRecentItemList = this.#filterByChatCollection(recentIdList, (item) => item.id);
			if (!Type.isArrayFilled(chatRecentItemList))
			{
				this.logger.warn('dialogReadAllCountersHandler: update recent list after filtering is empty, skipping');

				return;
			}

			const recentItemList = chatRecentItemList
				.map((item) => this.storeManager.store.getters['recentModel/getById'](String(item.id)))
				.filter(Boolean);

			this.#updateItems(recentItemList);
		}

		/**
		 * @param {Array<RecentModelState>} items
		 */
		#updateItems(items)
		{
			this.recentLocator.get('render').upsertItems(items);
			this.recentLocator.get('emitter').emit(RecentEventType.render.updateUIByRecentCollectionSizeIfNeeded, []);
		}

		/**
		 * @param {Array<object>} items
		 * @param {function(any): string|number} [idExtractor]
		 * @return {Array<object>}
		 */
		#filterByChatCollection(items, idExtractor = (item) => item.fields?.id)
		{
			if (!Type.isArrayFilled(items))
			{
				return [];
			}

			const chatCollection = this.storeManager.store.getters['recentModel/getChatIdCollection']();

			return items.filter((item) => {
				const id = idExtractor(item);

				return id && chatCollection.has(id);
			});
		}

		/**
		 * @param {Array<number>} chatIdList
		 * @return {Array<RecentModelState>}
		 */
		#getRecentItemsByChatIdList(chatIdList)
		{
			return this.storeManager.store.getters['recentModel/getByChatIdList'](chatIdList);
		}

		/**
		 * @param {Array<CounterModelState>} counterStateList
		 * @return {Array<number>}
		 */
		#extractChatIdFromCounterStates(counterStateList)
		{
			if (!Type.isArrayFilled(counterStateList))
			{
				return [];
			}

			const rawChatIdList = counterStateList
				.map((counterState) => {
					if (counterState.type === CounterType.comment)
					{
						return counterState.parentChatId;
					}

					return counterState.chatId;
				})
				.filter(Boolean)
			;

			return unique(rawChatIdList);
		}
	}

	module.exports = ChatVuexService;
});
