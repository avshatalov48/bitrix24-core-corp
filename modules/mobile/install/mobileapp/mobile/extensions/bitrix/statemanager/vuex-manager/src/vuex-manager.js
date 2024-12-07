/* eslint-disable flowtype/require-return-type */
/* eslint-disable no-underscore-dangle */
/* eslint-disable @bitrix24/bitrix24-rules/no-pseudo-private */

/**
 * @module statemanager/vuex-manager/vuex-manager
 */
jn.define('statemanager/vuex-manager/vuex-manager', (require, exports, module) => {
	const { Type } = require('type');
	const { MutationManager } = require('statemanager/vuex-manager/mutation-manager');
	const { StateStorage } = require('statemanager/vuex-manager/storage/base');
	const { SharedStorage } = require('statemanager/vuex-manager/storage/shared-storage');
	const { Uuid } = require('utils/uuid');
	const { Logger } = require('utils/logger');
	const { Store } = require('statemanager/vuex');

	const StateStorageSaveStrategy = Object.freeze({
		afterEachMutation: 'afterEachMutation', // saving the state into storage after each mutation
		whenNewStoreInit: 'whenNewStoreInit', // saving the state into storage before creating a new manager
	});

	/**
	 * @class VuexManager
	 * Designed to use the same store in multiple JavaScript-contexts and to subscribe to individual store mutations.
	 */
	class VuexManager
	{
		/**
		 * @param {Store} store - Vuex Store instance
		 */
		constructor(store)
		{
			this.logger = new Logger();
			// this.logger.enable('log');
			this.logger.enable('info');
			this.logger.enable('warn');

			if (!(store instanceof Store))
			{
				throw new TypeError('VuexManager: store must be an instance of Store.');
			}

			/** @private */
			this.vuexStore = store;
			/** @private */
			this.unsubscribeStoreMutations = () => {};
			/** @private */
			this.mutation = null;
			/** @private */
			this.handleMutation = this.onMutation.bind(this);
			/** @private */
			this.handleCreateInitialState = this.#createInitialState.bind(this);
			/** @private */
			this.storage = null;

			/** @private */
			this.storageNamespace = 'vuex-manager-store-';
			/** @private */
			this.eventNamespace = 'VuexManager::store::';

			/** @private */
			this.multiContextOptions = {
				storeName: null,
				sharedModuleList: new Set(),
				shareState: false,
				stateStorage: SharedStorage,
				stateStorageSaveStrategy: StateStorageSaveStrategy.afterEachMutation,
				isMainManager: true,
				clearStateStorage: false,
				onBeforeReplaceState: (state) => state,
			};

			/** @private */
			this.contextUuid = null;
			/** @private */
			this.storageKey = null;
			/** @private */
			this.eventPrefix = null;

			/** @private */
			this.shouldPostMutation = true;
		}

		get isMultiContextMode()
		{
			return this.multiContextOptions.storeName !== null;
		}

		get store()
		{
			return this.vuexStore;
		}

		/**
		 * @return {VuexManager}
		 */
		setLogger(logger)
		{
			if (!(logger instanceof Logger))
			{
				throw new TypeError('VuexManager.setLogger: logger must be an instance of Logger');
			}

			this.logger = logger;

			return this;
		}

		/**
		 * Sets synchronization settings between multiple JavaScript-contexts.
		 *
		 * @param {object} options
		 * @param {string} options.storeName - The unique name of the store.
		 * @param {boolean} [options.shareState=false] - Should the VuexManager pass state in the mutation event?
		 * @param {Set} [options.sharedModuleList=new Set()] - List of modules whose mutations the VuexManager shares
		 * with other contexts.
		 *
		 * @param {Storage} [options.stateStorage=SharedStorage] - The class that will be used to store state
		 * and initialize it in a new JS-context.
		 *
		 * @param {boolean} [options.clearStateStorage=false] - Should the VuexManager clear the state store
		 * before initialization?
		 *
		 * @param {string} [options.stateStorageSaveStrategy] - When is it necessary to save the state
		 * required for initialization in other contexts? Use only with buildAsync() after it
		 *
		 * @param {boolean} [options.isMainManager=true] - A flag indicating that this manager is initialized first
		 * (used in whenNewStoreInit storage save strategy)
		 *
		 * @param {function} [options.onBeforeReplaceState=(state) => state] -
		 * A handler that is called after raising the state from storage
		 * and before replacing the current state. Can be used to initialize storage objects.
		 * Receives a state as input and must return the state.
		 *
		 * @returns {VuexManager}
		 */
		enableMultiContext(options = {})
		{
			const isValidStoreName = Type.isStringFilled(options.storeName);
			if (!isValidStoreName)
			{
				throw new Error('VuexManager: options.storeName must be a filled string.');
			}

			if (!Type.isUndefined(options.sharedModuleList))
			{
				if (!(options.sharedModuleList instanceof Set))
				{
					throw new TypeError('VuexManager: options.sharedModuleList must be a Set of string.');
				}

				this.multiContextOptions.sharedModuleList = options.sharedModuleList;
			}

			if (!Type.isUndefined(options.shareState))
			{
				if (!Type.isBoolean(options.shareState))
				{
					throw new TypeError('VuexManager: options.shareState must be a boolean value.');
				}

				this.multiContextOptions.shareState = options.shareState;

				if (options.shareState)
				{
					this.logger.warn('VuexManager: passing state between contexts can have a big performance impact.');
				}
			}

			if (!Type.isUndefined(options.clearStateStorage))
			{
				if (!Type.isBoolean(options.clearStateStorage))
				{
					throw new TypeError('VuexManager: options.clearStateStorage must be a boolean value.');
				}

				this.multiContextOptions.clearStateStorage = options.clearStateStorage;
			}

			if (!Type.isUndefined(options.stateStorageSaveStrategy))
			{
				if (!StateStorageSaveStrategy[options.stateStorageSaveStrategy])
				{
					throw new TypeError('VuexManager: unknown synchronization strategy');
				}

				this.multiContextOptions.stateStorageSaveStrategy = options.stateStorageSaveStrategy;
			}

			if (!Type.isUndefined(options.isMainManager))
			{
				if (!Type.isBoolean(options.isMainManager))
				{
					throw new TypeError('VuexManager: options.isMainManager must be a boolean value.');
				}

				this.multiContextOptions.isMainManager = options.isMainManager;
			}

			if (!Type.isUndefined(options.onBeforeReplaceState))
			{
				if (!Type.isFunction(options.onBeforeReplaceState))
				{
					throw new TypeError('VuexManager: options.onBeforeReplaceState must be a function.');
				}

				this.multiContextOptions.onBeforeReplaceState = options.onBeforeReplaceState;
			}

			if (!Type.isUndefined(options.stateStorage))
			{
				if (!Type.isObject(options.stateStorage) || !(options.stateStorage instanceof StateStorage))
				{
					throw new TypeError('VuexManager: options.stateStorage must be an instance of StateStorage.');
				}

				this.multiContextOptions.stateStorage = options.stateStorage;
			}

			this.multiContextOptions.storeName = options.storeName;

			this.storageKey = `${this.storageNamespace}${this.multiContextOptions.storeName}`;
			this.eventPrefix = `${this.eventNamespace}${this.multiContextOptions.storeName}::`;
			this.mutationEventName = `${this.eventPrefix}mutation`;
			this.requestInitialStateEventName = `${this.eventPrefix}init-state`;
			this.initialStateReadyEventName = `${this.eventPrefix}init-state-ready`;
			this.contextUuid = Uuid.getV4();

			this.logger.warn(
				'VuexManager: multi context mode enabled for store',
				this.multiContextOptions.storeName,
				this.multiContextOptions,
			);

			return this;
		}

		/**
		 * @deprecated use buildAsync instead
		 * @see buildAsync
		 *
		 * @return {VuexManager}
		 */
		build()
		{
			this.logger.warn('VuexManager: build() method is deprecated, use buildAsync() instead');

			return this.#build();
		}

		/**
		 * Sets a mutation handler and subscribes to mutations from other JS-contexts
		 * if the multi context mode is enabled.
		 *
		 * @param {MutationManager} [mutationManager]
		 *
		 * @return {Promise}
		 */
		async buildAsync(mutationManager)
		{
			if (mutationManager && !(mutationManager instanceof MutationManager))
			{
				throw new TypeError('VuexManager: mutationManager must be an instance of MutationManager.');
			}

			if (this.multiContextOptions.stateStorageSaveStrategy === StateStorageSaveStrategy.whenNewStoreInit)
			{
				if (this.multiContextOptions.isMainManager)
				{
					this.#registerRequestInitialStateHandler();
				}
				else
				{
					await this.#requestInitialState();
				}
			}

			return this.#build(mutationManager);
		}

		/**
		 * @param {MutationManager} mutationManager
		 * @return {VuexManager}
		 */
		#build(mutationManager = new MutationManager())
		{
			this.mutation = mutationManager;

			if (!this.isMultiContextMode)
			{
				this.unsubscribeStoreMutations = this.vuexStore.subscribe(this.mutation.getHandler());

				return this;
			}

			// eslint-disable-next-line new-cap
			this.storage = new this.multiContextOptions.stateStorage({
				key: this.storageKey,
			});

			if (this.multiContextOptions.clearStateStorage)
			{
				this.clearStateStorage();
			}
			else
			{
				let state = this.storage.getState();
				if (state)
				{
					state = this.multiContextOptions.onBeforeReplaceState(state);

					this.vuexStore.replaceState(state);
				}
			}

			BX.addCustomEvent(this.mutationEventName, this.handleMutation);

			this.unsubscribeStoreMutations = this.vuexStore.subscribe(this.getMultiContextMutationHandler().bind(this));

			return this;
		}

		async #requestInitialState()
		{
			let resolvePromise;
			const promise = new Promise((resolve, reject) => {
				resolvePromise = resolve;
			});

			const stateReadyHandler = () => {
				BX.removeCustomEvent(this.initialStateReadyEventName, stateReadyHandler);

				resolvePromise();
			};

			const requestInitialStateEvent = {
				contextUuid: this.contextUuid,
			};

			BX.addCustomEvent(this.initialStateReadyEventName, stateReadyHandler);
			BX.postComponentEvent(this.requestInitialStateEventName, [requestInitialStateEvent]);

			return promise;
		}

		#registerRequestInitialStateHandler()
		{
			BX.addCustomEvent(this.requestInitialStateEventName, this.handleCreateInitialState);

			this.logger.info('VuexManager: ready to initialize state for other contexts');
		}

		#createInitialState(requestInitialStateEvent)
		{
			const {
				contextUuid,
			} = requestInitialStateEvent;

			this.saveState();

			BX.postComponentEvent(this.initialStateReadyEventName, []);

			this.logger.info('VuexManager: an initializing state was created based on the context request', contextUuid);
		}

		/**
		 * Unsubscribes from storage mutations and mutations from other contexts.
		 */
		disassemble()
		{
			this.unsubscribeStoreMutations();
			this.mutation = null;

			if (this.isMultiContextMode)
			{
				BX.removeCustomEvent(this.mutationEventName, this.handleMutation);
			}
		}

		on(mutationName, listener)
		{
			this.mutation.on(mutationName, listener);

			return this;
		}

		once(mutationName, listener)
		{
			this.mutation.once(mutationName, listener);

			return this;
		}

		off(mutationName, listener)
		{
			this.mutation.off(mutationName, listener);

			return this;
		}

		clearStateStorage()
		{
			this.storage.clearState();
		}

		/**
		 * @private
		 * @return {(function(*, *): void)|*}
		 */
		getMultiContextMutationHandler()
		{
			return async (mutation, state) => {
				const moduleName = mutation.type.split('/')[0];
				const sharedState = this.createSharedState(state);

				const isSharedMutation = this.multiContextOptions.sharedModuleList.size === 0
					|| this.multiContextOptions.sharedModuleList.has(moduleName)
				;
				if (this.shouldPostMutation && isSharedMutation)
				{
					if (this.multiContextOptions.stateStorageSaveStrategy === StateStorageSaveStrategy.afterEachMutation)
					{
						const dateStart = Date.now();

						this.storage.setState(sharedState);

						const dateFinish = Date.now();
						const storageTime = dateFinish - dateStart;

						this.logger.log(`VuexManager: initialization state saved in ${storageTime} ms.`);
					}

					this.postMutation(mutation, this.multiContextOptions.shareState ? sharedState : {});
				}

				await this.mutation.handle(mutation, this.multiContextOptions.shareState ? sharedState : {});
			};
		}

		saveState()
		{
			const state = this.vuexStore.state;
			const sharedState = this.createSharedState(state);
			this.storage.setState(sharedState);

			this.logger.info('VuexManager.saveState:', sharedState);
		}

		/**
		 * @private
		 * @param state
		 */
		createSharedState(state)
		{
			if (this.multiContextOptions.sharedModuleList.size === 0)
			{
				return state;
			}

			const sharedState = {};
			this.multiContextOptions.sharedModuleList.forEach((sharedModuleName) => {
				sharedState[sharedModuleName] = state[sharedModuleName];
			});

			return sharedState;
		}

		/**
		 * @private
		 * @param mutation
		 * @param state
		 */
		postMutation(mutation, state)
		{
			const eventData = {
				mutation,
				state,
				parentContextUuid: this.contextUuid,
			};

			BX.postComponentEvent(this.mutationEventName, [eventData]);
		}

		/**
		 * @private
		 * @param event
		 */
		onMutation(event)
		{
			const {
				mutation,
				state,
				parentContextUuid,
			} = event;

			if (parentContextUuid === this.contextUuid)
			{
				this.logger.log('VuexManager: ignore own mutation', mutation, state);

				return;
			}

			const mutationName = mutation.type;
			const payload = mutation.payload;

			this.logger.log('VuexManager: mutation received', mutation, state);

			// in order not to post mutations obtained from other contexts again
			this.shouldPostMutation = false;

			this.vuexStore.commit(mutationName, payload);

			this.shouldPostMutation = true;
		}
	}

	module.exports = {
		VuexManager,
		StateStorageSaveStrategy,
	};
});
