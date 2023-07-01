/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-pseudo-private */

/**
 * @module im/messenger/core
 */
jn.define('im/messenger/core', (require, exports, module) => {

	const { createStore } = require('statemanager/vuex');
	const { VuexManager } = require('statemanager/vuex-manager');

	const {
		applicationModel,
		recentModel,
		messagesModel,
		usersModel,
		dialoguesModel,
		filesModel,
	} = require('im/messenger/model');
	const {
		RecentCache,
		UsersCache,
		FilesCache,
	} = require('im/messenger/cache');
	const { Logger } = require('im/messenger/lib/logger');

	class CoreApplication
	{
		constructor()
		{
			this.inited = false;
			this.initPromise = new Promise((resolve) => {
				this.initPromiseResolver = resolve;
			});

			this.store = null;
			this.storeManager = null;
			this.host = currentDomain;
			this.userId = Number.parseInt(env.userId, 10) || 0;
			this.siteId = env.siteId || 's1';
			this.siteDir = env.siteDir || '/';

			this.initStore();
			this.fillStoreFromCache()
				.then(() => {
					this.initComplete();
				})
			;
		}

		initStore()
		{
			this.store = createStore({
				modules: {
					applicationModel,
					recentModel,
					messagesModel,
					usersModel,
					dialoguesModel,
					filesModel,
				}
			});

			this.storeManager =
				new VuexManager(this.getStore())
					.build()
			;
		}

		fillStoreFromCache()
		{
			const recentState = RecentCache.get();
			const usersState = UsersCache.get();
			const filesState = FilesCache.get();

			const cachePromiseList = [];

			if (recentState)
			{
				cachePromiseList.push(this.getStore().dispatch('recentModel/setState', recentState));
			}

			if (usersState)
			{
				cachePromiseList.push(this.getStore().dispatch('usersModel/setState', usersState));
			}

			if (filesState)
			{
				cachePromiseList.push(this.getStore().dispatch('filesModel/setState', filesState));
			}

			return Promise.all(cachePromiseList);
		}

		getHost()
		{
			return this.host;
		}

		getUserId()
		{
			return this.userId;
		}

		getSiteId()
		{
			return this.siteId;
		}

		getSiteDir()
		{
			return this.siteDir;
		}

		getStore()
		{
			return this.store;
		}

		getStoreManager()
		{
			return this.storeManager;
		}

		initComplete()
		{
			this.inited = true;
			this.initPromiseResolver(this);

			Logger.warn('CoreApplication.initComplete');
		}

		ready()
		{
			if (this.inited)
			{
				return Promise.resolve(this);
			}

			return this.initPromise;
		}
	}

	module.exports = {
		CoreApplication,
		core: new CoreApplication(),
	};
});
