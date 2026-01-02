/**
 * @module im/messenger/core/base/application
 */
jn.define('im/messenger/core/base/application', (require, exports, module) => {
	const { EntityReady } = require('entity-ready');
	const { clone, mergeImmutable } = require('utils/object');

	const { createStore } = require('statemanager/vuex');
	const { VuexManager } = require('statemanager/vuex-manager');

	const { VuexModelWriter } = require('im/messenger/db/model-writer');
	const { MessengerMutationManager } = require('im/messenger/lib/state-manager/vuex-manager/mutation-manager');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { Feature } = require('im/messenger/lib/feature');

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
		DraftRepository,
		CommentRepository,
		// CounterRepository,
		// SidebarFileRepository, TODO: The backend is not ready yet
		VoteRepository,
		TranscriptRepository,
	} = require('im/messenger/db/repository');
	const { Updater } = require('im/messenger/db/update');
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
		anchorModel,
	} = require('im/messenger/model');

	const {
		getLogger,
		Logger,
	} = require('im/messenger/lib/logger');
	const logger = getLogger('core');

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

			this.isReady = false;

			/** @type {MessengerCoreRepository} */
			this.repository = {
				dialog: null,
				user: null,
				file: null,
				message: null,
				reaction: null,
				smile: null,
				pinMessage: null,
				copilot: null,
				draft: null,
				comment: null,
				readMessageQueue: null,
				// sidebarFile: null, TODO: The backend is not ready yet
				vote: null,
				transcript: null,
			};

			this.store = null;
			this.storeManager = null;
			this.host = currentDomain;
			this.userId = Number.parseInt(env.userId, 10) || 0;
			this.siteId = env.siteId || 's1';
			this.siteDir = env.siteDir || '/';

			this.logger = Logger;

			if (this.getWaitingEntityId())
			{
				EntityReady.addCondition(this.getWaitingEntityId(), () => this.isReady);
			}
		}

		/**
		 * @protected
		 * @return {string|null}
		 */
		getWaitingEntityId()
		{
			return null;
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
			window.messengerDebug ??= {};
			window.messengerDebug.updater = new Updater();

			this.initRepository();
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
				this.repository.message.messagePushTable.drop();
				this.repository.tempMessage.tempMessageTable.drop();
				this.repository.reaction.reactionTable.drop();
				this.repository.queue.queueTable.drop();
				this.repository.smile.smileTable.drop();
				this.repository.pinMessage.pinTable.drop();
				this.repository.pinMessage.pinMessageTable.drop();
				this.repository.copilot.copilotTable.drop();
				this.repository.draft.draftTable.drop();
				this.repository.comment.commentTable.drop();
				// this.repository.counter.counterTable.drop();
				// this.repository.sidebarFile.sidebarFileTable.drop(); TODO: The backend is not ready yet
				this.repository.vote.voteTable.drop();
				this.repository.transcript.transcriptTable.drop();

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
				draft: new DraftRepository(),
				comment: new CommentRepository(),
				// sidebarFile: new SidebarFileRepository(),
				// counter: new CounterRepository(),
				vote: new VoteRepository(),
				transcript: new TranscriptRepository(),
			};
		}

		/**
		 * @desc force creation of connection instances in table classes – new DatabaseTable().
		 * This safe mode is launched only for a new and clean sqlite database.
		 * In all other cases, lazy access mode will be enabled.
		 * @void
		 */
		createDatabaseTableInstances()
		{
			if (!Feature.isLocalStorageEnabled)
			{
				return;
			}

			this.repository.option.optionTable.createDatabaseTableInstance();
			this.repository.recent.recentTable.createDatabaseTableInstance();
			this.repository.dialog.dialogTable.createDatabaseTableInstance();
			this.repository.dialog.internal.dialogInternalTable.createDatabaseTableInstance();
			this.repository.message.messageTable.createDatabaseTableInstance();
			this.repository.user.userTable.createDatabaseTableInstance();
			this.repository.file.fileTable.createDatabaseTableInstance();
			this.repository.reaction.reactionTable.createDatabaseTableInstance();
			this.repository.message.messagePushTable.createDatabaseTableInstance();
			this.repository.tempMessage.tempMessageTable.createDatabaseTableInstance();
			this.repository.queue.queueTable.createDatabaseTableInstance();
			this.repository.smile.smileTable.createDatabaseTableInstance();
			this.repository.pinMessage.pinTable.createDatabaseTableInstance();
			this.repository.pinMessage.pinMessageTable.createDatabaseTableInstance();
			this.repository.copilot.copilotTable.createDatabaseTableInstance();
			this.repository.draft.draftTable.createDatabaseTableInstance();
			this.repository.comment.commentTable.createDatabaseTableInstance();
			this.repository.transcript.transcriptTable.createDatabaseTableInstance();
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
				anchorModel,
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

		/**
		 * @param {AppStatusType} name
		 * @param {boolean} value
		 * @return {Promise<void>}
		 */
		async setAppStatus(name, value)
		{
			return this.store.dispatch('applicationModel/setStatus', { name, value });
		}

		initComplete()
		{
			this.isReady = true;

			if (this.getWaitingEntityId())
			{
				EntityReady.ready(this.getWaitingEntityId());
			}

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
