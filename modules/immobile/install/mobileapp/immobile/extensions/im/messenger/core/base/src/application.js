/**
 * @module im/messenger/core/base/application
 */
jn.define('im/messenger/core/base/application', (require, exports, module) => {
	const { clone, mergeImmutable } = require('utils/object');

	const { createStore } = require('statemanager/vuex');
	const { VuexManager } = require('statemanager/vuex-manager');

	const { updateDatabase } = require('im/messenger/db/update');
	const { VuexModelWriter } = require('im/messenger/db/model-writer');
	const { MessengerMutationManager } = require('im/messenger/lib/state-manager/vuex-manager/mutation-manager');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { Feature } = require('im/messenger/lib/feature');
	const {
		CacheNamespace,
		CacheName,
	} = require('im/messenger/const');
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
		PinMessageRepository,
		CopilotRepository,
		// CounterRepository,
		// SidebarFileRepository, TODO: The backend is not ready yet
	} = require('im/messenger/db/repository');
	const {
		applicationModel,
		recentModel,
		counterModel,
		messagesModel,
		usersModel,
		dialoguesModel,
		filesModel,
		sidebarModel,
		draftModel,
		queueModel,
		commentModel,
	} = require('im/messenger/model');

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
		/**
		 * @param {MessengerCoreInitializeOptions} config
		 * @return {Promise<void>}
		 */
		constructor(config = {})
		{
			/** @type {MessengerCoreInitializeOptions} */
			this.config = mergeImmutable(this.#getDefaultConfig(), config);

			this.inited = false;

			this.repository = {
				dialog: null,
				user: null,
				file: null,
				message: null,
				reaction: null,
				smile: null,
				pinMessage: null,
				copilot: null,
				// sidebarFile: null, TODO: The backend is not ready yet
			};

			this.store = null;
			this.storeManager = null;
			this.host = currentDomain;
			this.userId = Number.parseInt(env.userId, 10) || 0;
			this.siteId = env.siteId || 's1';
			this.siteDir = env.siteDir || '/';

			this.logger = Logger;
		}

		async init()
		{
			await this.initDatabase();
			this.initStore();
			this.initMutationManager();
			await this.initStoreManager();
			this.initLocalStorageWriter();

			this.initComplete();
		}

		async initDatabase()
		{
			if (!this.config.localStorage.enable)
			{
				Feature.disableLocalStorage();
			}

			if (this.config.localStorage.readOnly)
			{
				Feature.enableLocalStorageReadOnlyMode();
			}
			else
			{
				Feature.disableLocalStorageReadOnlyMode();
			}

			await this.updateDatabase();

			this.initRepository();
		}

		async updateDatabase()
		{
			if (!Feature.isLocalStorageEnabled)
			{
				return Promise.resolve();
			}

			return updateDatabase();
		}

		initRepository()
		{
			this.createRepository();

			if (!Feature.isLocalStorageEnabled)
			{
				if (this.config.localStorage.enable)
				{
					// if the database is programmatically supported, but is disabled by the user
					this.repository.drop();
				}

				this.createRepository();
			}
		}

		createRepository()
		{
			this.repository = this.getBaseRepository();

			this.repository.drop = () => {
				// TODO: temporary helper for development

				this.repository.option.optionTable.drop();
				this.repository.recent.recentTable.drop();
				this.repository.dialog.dialogTable.drop();
				this.repository.dialog.internal.dialogInternalTable.drop();
				this.repository.user.userTable.drop();
				this.repository.file.fileTable.drop();
				this.repository.message.messageTable.drop();
				this.repository.tempMessage.tempMessageTable.drop();
				this.repository.reaction.reactionTable.drop();
				this.repository.queue.queueTable.drop();
				this.repository.smile.smileTable.drop();
				this.repository.pinMessage.pinTable.drop();
				this.repository.pinMessage.pinMessageTable.drop();
				this.repository.copilot.copilotTable.drop();
				// this.repository.counter.counterTable.drop();
				// this.repository.sidebarFile.sidebarFileTable.drop(); TODO: The backend is not ready yet

				Application.storageById(CacheNamespace + CacheName.draft).clear();

				logger.warn('CoreApplication drop database complete');
			};
		}

		getBaseRepository()
		{
			return {
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
				pinMessage: new PinMessageRepository(),
				copilot: new CopilotRepository(),
				// sidebarFile: new SidebarFileRepository(),
				// counter: new CounterRepository(),
			};
		}

		getStoreModules()
		{
			return clone({
				applicationModel,
				recentModel,
				counterModel,
				messagesModel,
				usersModel,
				dialoguesModel,
				filesModel,
				sidebarModel,
				draftModel,
				queueModel,
				commentModel,
			});
		}

		initStore()
		{
			this.store = createStore({
				modules: this.getStoreModules(),
			});
		}

		initMutationManager()
		{
			this.mutationManager = new MessengerMutationManager();
		}

		async initStoreManager()
		{
			this.storeManager = new VuexManager(this.getStore());
			await this.storeManager.buildAsync(this.getMutationManager());
		}

		initLocalStorageWriter()
		{
			if (!Feature.isLocalStorageEnabled)
			{
				return;
			}

			this.localStorageWriter = new VuexModelWriter({
				repository: this.getRepository(),
				storeManager: this.getStoreManager(),
			});
		}

		getHost()
		{
			return this.host;
		}

		isCloud()
		{
			return MessengerParams.isCloud();
		}

		hasActiveCloudStorageBucket()
		{
			return MessengerParams.hasActiveCloudStorageBucket();
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
		 * @return {MessengerCoreRepository}
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
		 * @protected
		 * @return {MessengerMutationManager}
		 */
		getMutationManager()
		{
			return this.mutationManager;
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

		async setAppStatus(name, value)
		{
			return this.store.dispatch('applicationModel/setStatus', { name, value });
		}

		initComplete()
		{
			this.inited = true;

			logger.warn('CoreApplication.initComplete');
		}

		#getDefaultConfig()
		{
			return {
				localStorage: {
					enable: true,
					readOnly: false,
				},
			};
		}
	}

	module.exports = {
		CoreApplication,
	};
});
