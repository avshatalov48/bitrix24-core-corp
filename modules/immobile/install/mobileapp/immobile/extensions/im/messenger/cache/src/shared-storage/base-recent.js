/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/cache/base-recent
 */
jn.define('im/messenger/cache/base-recent', (require, exports, module) => {
	const { throttle } = require('utils/function');
	const { clone } = require('utils/object');

	const { Cache } = require('im/messenger/cache/base');
	const { Logger } = require('im/messenger/lib/logger');

	/**
	 * @class BaseRecentCache
	 */
	class BaseRecentCache extends Cache
	{
		/**
		 * @param options
		 * @param {MessengerCoreStoreManager} options.storeManager
		 * @param {string} options.name
		 * @param {Logger} options.logger
		 */
		constructor(options)
		{
			super({
				name: options.name,
			});

			this.storeManager = options.storeManager;
			/** @type {MessengerCoreStore} */
			this.store = options.storeManager.store;
			this.logger = options.logger || Logger;
			this.save = throttle(this.save, 10000, this);

			this.subscribeStoreEvents();
		}

		subscribeStoreEvents()
		{
			this.storeManager.on('recentModel/add', this.save);
			this.storeManager.on('recentModel/update', this.save);
			this.storeManager.on('recentModel/delete', this.save);
		}

		save()
		{
			let recentCollection = clone(this.store.getters['recentModel/getCollection']());
			recentCollection = recentCollection
				.sort(this.sortListByMessageDate)
				.filter((recentItem, index) => index < 50)
			;

			const dialogIdList = [];
			recentCollection.forEach((item) => {
				dialogIdList.push(item.id);
			});

			const dialoguesCollection = this.store.getters['dialoguesModel/getCollectionByIdList'](dialogIdList);
			const userCollection = this.store.getters['usersModel/getCollectionByIdList'](dialogIdList);
			const state = {
				recent: {
					collection: recentCollection,
				},
				dialogues: {
					collection: dialoguesCollection,
				},
				users: {
					collection: userCollection,
				},
			};

			// invalidation of recent elements without dialog
			state.recent.collection = state.recent.collection.filter((recentItem) => {
				if (state.dialogues.collection[recentItem.id])
				{
					return true;
				}

				this.logger.error(
					`${this.getClassName()}.save: there is no dialog ${recentItem.id} in model`,
					recentCollection,
					dialoguesCollection,
					userCollection,
				);

				return false;
			});

			this.logger.info(`${this.getClassName()}.save:`, state);

			return super.save(state);
		}

		sortListByMessageDate(a, b)
		{
			if (!a.pinned && b.pinned)
			{
				return 1;
			}

			if (a.pinned && !b.pinned)
			{
				return -1;
			}

			if (a.message && b.message)
			{
				const timestampA = new Date(a.message.date).getTime();
				const timestampB = new Date(b.message.date).getTime();

				return timestampB - timestampA;
			}

			return 0;
		}

		/**
		 * @desc get class name for logger
		 * @return {string}
		 */
		getClassName()
		{
			return this.constructor.name;
		}
	}

	module.exports = {
		BaseRecentCache,
	};
});
