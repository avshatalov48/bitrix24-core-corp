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
			const { dialogues, recentItems, users } = this.prepareDataForModels(items);

			return Promise.all([
				this.setDialoguesToModel(dialogues),
				this.setRecentItemsToModel(recentItems),
				this.setUsersToModel(users),
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
		 * @return {{dialogues: Array<object>, recentItems: Array<object>, users: Array<object>}}
		 */
		prepareDataForModels(items)
		{
			const result = {
				users: [],
				dialogues: [],
				recentItems: [],
			};

			[...items.values()].forEach((item) => {
				const itemData = item.customData;

				result.recentItems.push({
					id: item.dialogId,
					dateMessage: item.dateMessage,
				});

				if (item.isUser)
				{
					const user = itemData;
					result.users.push(user);

					result.dialogues.push(this.prepareUserForDialog(user));
				}

				if (item.isChat)
				{
					result.dialogues.push({
						...itemData,
						dialogId: item.dialogId,
					});
				}
			});

			return result;
		}

		/**
		 * @private
		 * @param userItem
		 * @return {{color, name, avatar, dialogId, type: string}}
		 */
		prepareUserForDialog(userItem)
		{
			return {
				dialogId: userItem.id,
				avatar: userItem.avatar,
				color: userItem.color,
				name: userItem.name,
				type: DialogType.user,
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
	}

	module.exports = { StoreUpdater };
});
