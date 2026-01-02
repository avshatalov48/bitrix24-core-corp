/**
 * @module im/messenger-v2/controller/recent/service/action/lib/handler
 */
jn.define('im/messenger-v2/controller/recent/service/action/lib/handler', (require, exports, module) => {
	const { Type } = require('type');
	const { UserProfile } = require('user-profile');
	const { Loc } = require('im/messenger/loc');
	const { ErrorType } = require('im/messenger/const');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { MessengerNotifier } = require('im/messenger/lib/ui/notification/messenger-notifier');

	const {
		RecentRest,
		ChatRest,
		UserRest,
	} = require('im/messenger/provider/rest');
	const {
		RecentDataProvider,
		ChatDataProvider,
	} = require('im/messenger/provider/data');

	const { clone } = require('utils/object');

	/**
	 * @param {MessengerCoreStore} store
	 * @param {string} id
	 * @return {?RecentModelState}
	 */
	function getRecentItemById(store, id)
	{
		return store.getters['recentModel/getById'](id);
	}

	/**
	 * @param {MessengerCoreStore} store
	 * @param {string} id
	 * @return {?DialoguesModelState}
	 */
	function getDialogById(store, id)
	{
		return clone(store.getters['dialoguesModel/getById'](id));
	}

	/**
	 * @param {MessengerCoreStore} store
	 * @param {string} id
	 * @return {?CounterModelState}
	 */
	function getCounterStateById(store, id)
	{
		const dialog = store.getters['dialoguesModel/getById'](id);
		if (!dialog)
		{
			return null;
		}

		return clone(store.getters['counterModel/getByChatId'](dialog.chatId));
	}

	/**
	 * @param {RecentLocator} recentLocator
	 */
	function renderRecent(recentLocator)
	{
		recentLocator.get('render')?.renderInstant();
	}

	/**
	 * @param {MessengerCoreStore} store
	 * @param {Logger} logger
	 * @param {RecentLocator} recentLocator
	 * @param {string} itemId
	 * @param {boolean} shouldMute
	 */
	async function handleMute({ store, logger, recentLocator }, itemId, shouldMute)
	{
		const dialog = getDialogById(store, itemId);
		if (Type.isUndefined(dialog))
		{
			return;
		}

		const userId = MessengerParams.getUserId();
		const muteList = new Set(dialog.muteList);

		if (shouldMute)
		{
			muteList.add(userId);
		}
		else
		{
			muteList.delete(userId);
		}

		await store.dispatch('dialoguesModel/set', [{
			dialogId: itemId,
			muteList: [...muteList],
		}]);

		await store.dispatch('counterModel/setDisableChatCounter', {
			chatId: dialog.chatId,
			disabled: shouldMute,
		});

		try
		{
			await ChatRest.mute({ dialogId: itemId, shouldMute });
		}
		catch (error)
		{
			logger.error('Recent item mute error: ', error?.error?.() ?? error?.message);
			await store.dispatch('dialoguesModel/set', [dialog]);
			await store.dispatch('counterModel/setDisableChatCounter', {
				chatId: dialog.chatId,
				disabled: !shouldMute,
			});

			renderRecent(recentLocator);
		}
	}

	const handlers = {
		/**
		 * @param {{store: MessengerCoreStore, logger: Logger, recentLocator: RecentLocator}} deps
		 * @param {string} itemId
		 * @returns {Promise<void>}
		 */
		hide: async ({ store, recentLocator, logger }, itemId) => {
			const recentItem = getRecentItemById(store, itemId);
			if (Type.isUndefined(recentItem))
			{
				return;
			}
			const counterState = getCounterStateById(store, itemId);

			await store.dispatch('recentModel/hideByNavigationTabs', {
				id: recentItem.id,
				fromTabs: [recentLocator.get('id')],
			});
			if (Type.isPlainObject(counterState))
			{
				await store.dispatch('counterModel/delete', { chatId: counterState?.chatId });
			}
			renderRecent(recentLocator);

			try
			{
				await RecentRest.hideChat({ dialogId: recentItem.id });
			}
			catch (error)
			{
				logger.error('handler recent item hide error: ', error?.error?.() ?? error?.message);
				await store.dispatch('recentModel/setByNavigationTabs', {
					tabs: [recentLocator.get('id')],
					itemList: [recentItem],
				});
				if (Type.isPlainObject(counterState))
				{
					await store.dispatch('counterModel/setList', { counterList: [counterState] });
				}
				renderRecent(recentLocator);
			}
		},

		/**
		 * @param {{store: MessengerCoreStore, logger: Logger}} deps
		 * @param {string} itemId
		 * @returns {Promise<void>}
		 */
		leave: async ({ store, logger }, itemId) => {
			const recentItem = getRecentItemById(store, itemId);
			if (Type.isUndefined(recentItem))
			{
				return;
			}
			const tabs = store.getters['recentModel/getTabsContainsItem'](recentItem.id);

			const recentProvider = new RecentDataProvider();

			try
			{
				await recentProvider.deleteFromSource(RecentDataProvider.source.model, { dialogId: recentItem.id });
			}
			catch (err)
			{
				logger.error('handler recent item leave error: ', err);
			}

			try
			{
				await ChatRest.leave({ dialogId: recentItem.id });
				const chatProvider = new ChatDataProvider();
				await recentProvider.delete({ dialogId: recentItem.id });
				await chatProvider.delete({ dialogId: recentItem.id });
			}
			catch (error)
			{
				logger.error('handler recent item leave error: ', error?.error?.() ?? error?.message);
				await store.dispatch('recentModel/setByNavigationTabs', {
					tabs,
					itemList: [recentItem],
				});
			}
		},

		/**
		 * @param {{store: MessengerCoreStore, logger: Logger, recentLocator: RecentLocator}} deps
		 * @param {string} itemId
		 * @returns {Promise<void>}
		 */
		pin: async ({ store, logger, recentLocator }, itemId) => {
			await store.dispatch('recentModel/update', [{ id: itemId, pinned: true }]);
			renderRecent(recentLocator);

			try
			{
				await RecentRest.pinChat(itemId);
			}
			catch (error)
			{
				logger.error('handler recent item pin error: ', error);
				const errorCode = error?.[0]?.code;
				if (errorCode === ErrorType.recent.maxPin)
				{
					const { Alert, ButtonType } = require('alert');
					Alert.confirm(
						null,
						Loc.getMessage('IMMOBILE_MESSENGER_CONTROLLER_RECENT_SERVICE_ACTION_PIN_ERROR_MAX_PINNED'),
						[{ type: ButtonType.DEFAULT }],
					);
				}
				await store.dispatch('recentModel/update', [{ id: itemId, pinned: false }]);
			}
		},

		/**
		 * @param {{store: MessengerCoreStore, logger: Logger, recentLocator: RecentLocator}} deps
		 * @param {string} itemId
		 * @returns {Promise<void>}
		 */
		unpin: async ({ store, logger, recentLocator }, itemId) => {
			await store.dispatch('recentModel/update', [{ id: itemId, pinned: false }]);
			renderRecent(recentLocator);

			try
			{
				await RecentRest.unpinChat(itemId);
			}
			catch (error)
			{
				logger.error('handler recent item unpin error: ', error);
				await store.dispatch('recentModel/update', [{ id: itemId, pinned: true }]);
			}
		},

		/**
		 * @param {{store: MessengerCoreStore, logger: Logger, recentLocator: RecentLocator}} deps
		 * @param {string} itemId
		 * @returns {Promise<void>}
		 */
		read: async ({ store, logger, recentLocator }, itemId) => {
			const recentItem = getRecentItemById(store, itemId);
			if (Type.isUndefined(recentItem))
			{
				return;
			}

			const counterState = getCounterStateById(store, itemId);

			if (!counterState)
			{
				return;
			}

			await store.dispatch('counterModel/setList', {
				counterList: [{
					...counterState,
					counter: 0,
				}],
			});
			await store.dispatch('recentModel/update', [{ id: itemId, unread: false }]);
			renderRecent(recentLocator);

			try
			{
				await RecentRest.readChat({ dialogId: itemId });
			}
			catch (error)
			{
				logger.error('handler recent item read error: ', error?.error?.() ?? error?.message);
				await store.dispatch('counterModel/setList', {
					counterList: [{
						...counterState,
						counter: 0,
					}],
				});
				await store.dispatch('recentModel/update', [recentItem]);
				renderRecent(recentLocator);
			}
		},

		/**
		 * @param {{store: MessengerCoreStore, logger: Logger, recentLocator: RecentLocator}} deps
		 * @param {string} itemId
		 * @returns {Promise<void>}
		 */
		unread: async ({ store, logger, recentLocator }, itemId) => {
			const recentItem = getRecentItemById(store, itemId);
			if (Type.isUndefined(recentItem))
			{
				return;
			}

			await store.dispatch('recentModel/update', [{ id: itemId, unread: true }]);
			renderRecent(recentLocator);

			try
			{
				await RecentRest.unreadChat({ dialogId: itemId });
			}
			catch (error)
			{
				logger.error('handler recent item unread error: ', error?.error?.() ?? error?.message);
				await store.dispatch('recentModel/update', [recentItem]);
				renderRecent(recentLocator);
			}
		},

		/**
		 * @param {{store: MessengerCoreStore, logger: Logger, recentLocator: RecentLocator}} deps
		 * @param {string} itemId
		 * @returns {Promise<void>}
		 */
		mute: async (deps, itemId) => handleMute(deps, itemId, true),

		/**
		 * @param {{store: MessengerCoreStore, logger: Logger, recentLocator: RecentLocator}} deps
		 * @param {string} itemId
		 * @returns {Promise<void>}
		 */
		unmute: async (deps, itemId) => handleMute(deps, itemId, false),

		/**
		 * @param {*} _deps
		 * @param {string} itemId
		 * @returns {Promise<void>}
		 */
		inviteResend: async (_deps, itemId) => {
			try
			{
				await UserRest.resendInvite({ userId: itemId });
				MessengerNotifier.show({
					message: Loc.getMessage('IMMOBILE_MESSENGER_CONTROLLER_RECENT_SERVICE_ACTION_INVITE_RESEND_DONE'),
				});
			}
			catch (response)
			{
				if (response.status === 'error')
				{
					MessengerNotifier.show({
						message: response.errors.map((element) => element.message).join('. '),
					});
				}
			}
		},

		/**
		 * @param {{store: MessengerCoreStore, logger: Logger, recentLocator: RecentLocator}} deps
		 * @param {string} itemId
		 * @returns {Promise<void>}
		 */
		inviteCancel: async ({ store, logger, recentLocator }, itemId) => {
			const recentItem = getRecentItemById(store, itemId);
			if (Type.isUndefined(recentItem))
			{
				return;
			}

			await store.dispatch('recentModel/delete', { id: recentItem.id });
			renderRecent(recentLocator);

			try
			{
				await UserRest.cancelInvite({ userId: itemId });
			}
			catch (response)
			{
				if (response.status === 'error')
				{
					MessengerNotifier.show({
						message: response.errors.map((element) => element.message).join('. '),
					});
				}

				logger.error('handler recent item inviteCancel error: ', response);
				await store.dispatch('recentModel/update', [recentItem]);
				renderRecent(recentLocator);
			}
		},

		/**
		 * @param {*} _deps
		 * @param {string} itemId
		 * @returns {Promise<void>}
		 */
		profile: async (_deps, itemId) => {
			let ownerId = itemId;
			if (Type.isString(itemId))
			{
				const numId = Number(itemId);
				ownerId = Number.isNaN(numId) ? itemId : numId;
			}
			void UserProfile.open({ ownerId });
		},
	};

	module.exports = { handlers };
});
