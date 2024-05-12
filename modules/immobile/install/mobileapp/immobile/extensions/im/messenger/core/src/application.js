/**
 * @module im/messenger/core/application
 */
jn.define('im/messenger/core/application', (require, exports, module) => {
	const { createStore } = require('statemanager/vuex');
	const { VuexManager } = require('statemanager/vuex-manager');

	const { updateDatabase } = require('im/messenger/db/update');
	const { VuexModelWriter } = require('im/messenger/db/model-writer');
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
			/** @type {MessengerCoreInitializeOptions} */
			this.config = {};
			this.inited = false;

			this.repository = {
				dialog: null,
				user: null,
				file: null,
				message: null,
				reaction: null,
				smile: null,
				pinMessage: null,
			};

			this.store = null;
			this.storeManager = null;
			this.host = currentDomain;
			this.userId = Number.parseInt(env.userId, 10) || 0;
			this.siteId = env.siteId || 's1';
			this.siteDir = env.siteDir || '/';

			this.logger = Logger;
		}

		/**
		 * @param {MessengerCoreInitializeOptions} config
		 * @return {Promise<void>}
		 */
		async init(config)
		{
			this.config = config ?? {};

			await this.initDatabase();
			this.initStore();
			this.initLocalStorageWriter();

			this.initComplete();
		}

		async initDatabase()
		{
			if (!this.config.localStorageEnable)
			{
				Feature.disableLocalStorage();
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
				if (this.config.localStorageEnable)
				{
					// if the database is programmatically supported, but is disabled by the user
					this.repository.drop();
				}

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
				pinMessage: new PinMessageRepository(),
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
				this.repository.pinMessage.pinTable.drop();
				this.repository.pinMessage.pinMessageTable.drop();

				Application.storageById(CacheNamespace + CacheName.chatRecent).clear();
				Application.storageById(CacheNamespace + CacheName.copilotRecent).clear();
				Application.storageById(CacheNamespace + CacheName.draft).clear();

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
		 *  pinMessage: PinMessageRepository,
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

			logger.warn('CoreApplication.initComplete');
		}
	}

	module.exports = {
		CoreApplication,
	};
});
