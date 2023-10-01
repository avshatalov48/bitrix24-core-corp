/* eslint-disable flowtype/require-return-type */

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
		sidebarModel,
		draftModel,
	} = require('im/messenger/model');
	const {
		RecentCache,
		UsersCache,
		FilesCache,
		DraftCache,
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
			// eslint-disable-next-line promise/catch-or-return
			this.fillStoreFromCache()
				.then(this.initComplete.bind(this))
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
					sidebarModel,
					draftModel,
				},
			});

			this.storeManager = new VuexManager(this.getStore())
				.build()
			;
		}

		fillStoreFromCache()
		{
			const recentState = RecentCache.get();
			const usersState = UsersCache.get();
			const filesState = FilesCache.get();
			const draftState = DraftCache.get();

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

			if (draftState)
			{
				cachePromiseList.push(this.getStore().dispatch('draftModel/setState', draftState));
			}

			return Promise.all(cachePromiseList);
		}

		getHost()
		{
			return this.host;
		}

		isCloud()
		{
			return CoreApplication.getOption('isCloud', false);
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

		/**
		 * @return {MessengerCoreStore}
		 */
		getStore()
		{
			return this.store;
		}

		/**
		 * @return {MessengerCoreStoreManager}
		 */
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

		/**
		 * @private
		 */
		static getOption(name, defaultValue)
		{
			const options = jnExtensionData.get('im:messenger/core');

			// eslint-disable-next-line no-prototype-builtins
			if (options.hasOwnProperty(name))
			{
				return options[name];
			}

			return defaultValue;
		}
	}

	module.exports = {
		CoreApplication,
		core: new CoreApplication(),
	};
});
