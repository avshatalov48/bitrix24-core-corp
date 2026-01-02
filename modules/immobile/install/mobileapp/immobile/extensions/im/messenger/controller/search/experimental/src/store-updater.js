/**
 * @module im/messenger/controller/search/experimental/store-updater
 */
jn.define('im/messenger/controller/search/experimental/store-updater', (require, exports, module) => {
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { DialogType } = require('im/messenger/const');
	class StoreUpdater
	{
		constructor()
		{
			/**
			 * @private
			 * @type {MessengerCoreStore}
			 */
			this.store = serviceLocator.get('core').getStore();
		}

		/**
		 * @param {Map<string, RecentSearchItem>} items
		 * @return {Promise<Awaited<*>[]>}
		 */
		async update(items)
		{
			const { dialogues, recentItems, users, copilots } = this.prepareDataForModels(items);

			return Promise.all([
				this.setDialoguesToModel(dialogues),
				this.setRecentItemsToModel(recentItems),
				this.setUsersToModel(users),
				this.#setCopilotsToModel(copilots),
			]);
		}

		/**
		 * @param {Map<string, RecentSearchItem>} items
		 * @return {Promise<*>}
		 */
		async updateSearchSession(items)
		{
			const { recentItems } = this.prepareDataForModels(items);

			return this.setRecentSearchItems(recentItems);
		}

		/**
		 * @private
		 * @param {Map<string, RecentSearchItem>} items
		 * @return {
		 * {dialogues: Array<object>,
		 * recentItems: Array<object>,
		 * users: Array<object>,
		 * copilots: Array<object>
		 * }}
		 */
		prepareDataForModels(items)
		{
			const result = {
				users: [],
				dialogues: [],
				recentItems: [],
				copilots: [],
			};

			[...items.values()].forEach((item) => {
				result.recentItems.push({
					id: item.dialogId,
					dateMessage: item.dateMessage,
				});

				if (item.isUser)
				{
					result.users.push(item.customData.user);

					result.dialogues.push(this.prepareUserForDialog(item.customData));
				}

				if (item.isChat)
				{
					result.dialogues.push({
						...item.customData.chat,
						dialogId: item.dialogId,
					});
				}

				if (item.customData.copilot)
				{
					const role = item.customData.copilot.code;
					const roles = { [role]: item.customData.copilot };
					const chats = [{ dialogId: item.dialogId, role }];

					const copilotItem = {
						dialogId: item.dialogId,
						chats,
						aiProvider: '',
						roles,
					};

					result.copilots.push(copilotItem);
				}
			});

			return result;
		}

		/**
		 * @private
		 * @param {RecentProviderItemCustomData} customData
		 * @return {RecentProviderUserDataForDialogModel}
		 */
		prepareUserForDialog(customData)
		{
			const { user, chat } = customData;

			return {
				dialogId: user.id,
				avatar: user.avatar,
				color: user.color,
				name: user.name,
				type: DialogType.user,
				...chat,
			};
		}

		/**
		 * @private
		 * @param {Array<object>} items
		 * @return {Promise<any>}
		 */
		setRecentSearchItems(items)
		{
			return this.store.dispatch('recentModel/searchModel/set', items);
		}

		/**
		 * @param {Array<object>} users
		 */
		async setUsersToModel(users)
		{
			return this.store.dispatch('usersModel/merge', users);
		}

		/**
		 * @private
		 * @param {Array<object>} recentItems
		 */
		async setRecentItemsToModel(recentItems)
		{
			return this.store.dispatch('recentModel/update', recentItems);
		}

		/**
		 * @private
		 * @param {Array<object>} dialogues
		 */
		async setDialoguesToModel(dialogues)
		{
			return this.store.dispatch('dialoguesModel/set', dialogues);
		}

		/**
		 * @param {Array<object>} copilots
		 */
		async #setCopilotsToModel(copilots)
		{
			return this.store.dispatch('dialoguesModel/copilotModel/setCollection', copilots);
		}
	}

	module.exports = { StoreUpdater };
});
