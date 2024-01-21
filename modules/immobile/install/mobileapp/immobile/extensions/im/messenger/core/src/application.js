/**
 * @module im/messenger/core/application
 */
jn.define('im/messenger/core/application', (require, exports, module) => {
	const { createStore } = require('statemanager/vuex');
	const { VuexManager } = require('statemanager/vuex-manager');

	const { updateDatabase } = require('im/messenger/db/update');
	const { VuexModelWriter } = require('im/messenger/db/model-writer');
	const { Settings } = require('im/messenger/lib/settings');
	const {
		OptionRepository,
		RecentRepository,
		DialogRepository,
		UserRepository,
		FileRepository,
		MessageRepository,
		TempMessageRepository,
		ReactionRepository,
		SmileRepository,
		QueueRepository,
	} = require('im/messenger/db/repository');
	const {
		applicationModel,
		recentModel,
		messagesModel,
		usersModel,
		dialoguesModel,
		filesModel,
		sidebarModel,
		draftModel,
		queueModel,
	} = require('im/messenger/model');
	const {
		RecentCache,
		DraftCache,
	} = require('im/messenger/cache');
	const {
		LoggerManager,
		Logger,
	} = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('core');

	/**
	 * @class CoreApplication
	 */
	class CoreApplication
	{
		constructor()
		{
			this.inited = false;
			this.initPromise = new Promise((resolve) => {
				this.initPromiseResolver = resolve;
			});

			this.repository = {
				dialog: null,
				user: null,
				file: null,
				message: null,
				reaction: null,
				smile: null,
			};

			this.store = null;
			this.storeManager = null;
			this.host = currentDomain;
			this.userId = Number.parseInt(env.userId, 10) || 0;
			this.siteId = env.siteId || 's1';
			this.siteDir = env.siteDir || '/';

			this.loggerManager = LoggerManager.getInstance();
			this.logger = Logger;

			this.init()
				.then(() => {
					this.initComplete();
				})
				.catch((error) => {
					logger.error(error);
				})
			;
		}

		async init()
		{
			await this.updateDatabase();
			this.initRepository();
			this.initStore();
			this.initLocalStorageWriter();
			await this.fillStoreFromCache();
		}

		async updateDatabase()
		{
			if (!Settings.isLocalStorageEnabled)
			{
				return Promise.resolve();
			}

			return updateDatabase();
		}

		initRepository()
		{
			this.createRepository();

			if (!Settings.isLocalStorageEnabled)
			{
				this.repository.drop();
				this.createRepository();
			}
		}

		createRepository()
		{
			this.repository = {
				option: new OptionRepository(),
				recent: new RecentRepository(),
				dialog: new DialogRepository(),
				user: new UserRepository(),
				file: new FileRepository(),
				message: new MessageRepository(),
				tempMessage: new TempMessageRepository(),
				reaction: new ReactionRepository(),
				queue: new QueueRepository(),
				smile: new SmileRepository(),
			};

			this.repository.drop = () => {
				// TODO: temporary helper for development

				this.repository.option.optionTable.drop();
				this.repository.recent.recentTable.drop();
				this.repository.dialog.dialogTable.drop();
				this.repository.user.userTable.drop();
				this.repository.file.fileTable.drop();
				this.repository.message.messageTable.drop();
				this.repository.tempMessage.tempMessageTable.drop();
				this.repository.reaction.reactionTable.drop();
				this.repository.queue.queueTable.drop();
				this.repository.smile.smileTable.drop();

				logger.warn('CoreApplication drop database complete');
			};
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
					queueModel,
				},
			});

			this.storeManager = new VuexManager(this.getStore())
				.build()
			;
		}

		initLocalStorageWriter()
		{
			if (!Settings.isLocalStorageEnabled)
			{
				return;
			}

			this.localStorageWriter = new VuexModelWriter({
				repository: this.getRepository(),
				storeManager: this.getStoreManager(),
			});
		}

		async fillStoreFromCache()
		{
			// if (!Settings.isLocalStorageEnabled)
			// {
			//
			// }

			this.recentCache = new RecentCache({
				storeManager: this.getStoreManager(),
			});

			const cache = this.recentCache.get();
			logger.log('CoreApplication.fillStoreFromCache cache:', cache);
			if (cache && cache.users)
			{
				await this.getStore().dispatch('usersModel/setState', cache.users);
			}

			if (cache && cache.dialogues)
			{
				await this.getStore().dispatch('dialoguesModel/setState', cache.dialogues);
			}

			if (cache && cache.recent)
			{
				// invalidation of recent elements without dialog
				cache.recent.collection = cache.recent.collection.filter((recentItem) => {
					if (cache.dialogues.collection[recentItem.id])
					{
						return true;
					}

					logger.error(
						`RecentCache.save: there is no dialog ${recentItem.id} in model`,
						cache.recent,
						cache.dialogues,
						cache.users,
					);

					return false;
				});

				await this.getStore().dispatch('recentModel/setState', cache.recent);
			}

			const draftState = DraftCache.get();
			if (draftState)
			{
				await this.getStore().dispatch('draftModel/setState', draftState);
			}
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

		getLoggerManager()
		{
			return this.loggerManager;
		}

		/**
		 * @return {{
		 *  option: OptionRepository,
		 *  recent: RecentRepository,
		 *  dialog: DialogRepository,
		 *  file: FileRepository,
		 *  user: UserRepository,
		 *  message: MessageRepository,
		 *  tempMessage: TempMessageRepository,
		 *  reaction: ReactionRepository
		 *  queue: QueueRepository
		 *  smile: SmileRepository,
		 * }}
		 */
		getRepository()
		{
			return this.repository;
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

		getAppStatus()
		{
			return this.store.getters['applicationModel/getStatus']();
		}

		setAppStatus(name, value)
		{
			return this.store.dispatch('applicationModel/setStatus', { name, value });
		}

		initComplete()
		{
			this.inited = true;
			this.initPromiseResolver(this);

			logger.warn('CoreApplication.initComplete');
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
	};
});
