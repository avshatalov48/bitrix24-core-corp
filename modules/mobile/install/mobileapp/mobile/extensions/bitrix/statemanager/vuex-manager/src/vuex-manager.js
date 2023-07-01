/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-pseudo-private */

/**
 * @module statemanager/vuex-manager/vuex-manager
 */
jn.define('statemanager/vuex-manager/vuex-manager', (require, exports, module) => {

	const { MutationManager } = require('statemanager/vuex-manager/mutation-manager');
	const { StateStorage } = require('statemanager/vuex-manager/storage/base');
	const { SharedStorage } = require('statemanager/vuex-manager/storage/shared-storage');
	const { Uuid } = require('utils/uuid');
	const { Logger } = require('utils/logger');
	const { Store } = require('statemanager/vuex');

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
			this.logger.enable('warn');

			if (!(store instanceof Store))
			{
				throw new Error('VuexManager: store must be an instance of Store.');
			}

			this._store = store;
			this._unsubscribeStoreMutations = () => {};
			this._mutation = null;
			this._handleMutation = this._onMutation.bind(this);
			this._storage = null;

			this._storageNamespace = 'vuex-manager-store-';
			this._eventNamespace = 'VuexManager::store::';

			this._multiContextOptions = {
				storeName: null,
				shareState: false,
				stateStorage: SharedStorage,
				clearStateStorage: false,
				onBeforeReplaceState: (state) => state,
			};

			this._contextUuid = null;
			this._storageKey = null;
			this._eventPrefix = null;

			this._shouldPostMutation = true;
		}

		get isMultiContextMode()
		{
			return this._multiContextOptions.storeName !== null;
		}

		get store()
		{
			return this._store;
		}

		/**
		 * Sets synchronization settings between multiple JavaScript-contexts.
		 *
		 * @param {object} options
		 * @param {string} options.storeName - The unique name of the store.
		 * @param {boolean} [options.shareState=false] - Should the VuexManager pass state in the mutation event?
		 *
		 * @param {Storage} [options.stateStorage=SharedStorage] - The class that will be used to store state
		 * and initialize it in a new JS-context.
		 *
		 * @param {boolean} [options.clearStateStorage=false] - Should the VuexManager clear the state store
		 * before initialization?
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
			const isValidStoreName = typeof options.storeName === 'string' && options.storeName.length !== 0;
			if (!isValidStoreName)
			{
				throw new Error('VuexManager: options.storeName must be a filled string.');
			}

			if (typeof options.shareState !== 'undefined')
			{
				if (typeof options.shareState !== 'boolean')
				{
					throw new Error('VuexManager: options.shareState must be a boolean value.');
				}

				this._multiContextOptions.shareState = options.shareState;

				if (options.shareState)
				{
					this.logger.warn('VuexManager: passing state between contexts can have a big performance impact.');
				}
			}

			if (typeof options.clearStateStorage !== 'undefined')
			{
				if (typeof options.clearStateStorage !== 'boolean')
				{
					throw new Error('VuexManager: options.clearStateStorage must be a boolean value.');
				}

				this._multiContextOptions.clearStateStorage = options.clearStateStorage;
			}

			if (typeof options.onBeforeReplaceState !== 'undefined')
			{
				if (typeof options.onBeforeReplaceState !== 'function')
				{
					throw new Error('VuexManager: options.onBeforeReplaceState must be a function.');
				}

				this._multiContextOptions.onBeforeReplaceState = options.onBeforeReplaceState;
			}

			if (typeof options.stateStorage !== 'undefined')
			{
				if ((typeof options.stateStorage !== 'object') || !(options.stateStorage instanceof StateStorage))
				{
					throw new Error('VuexManager: options.stateStorage must be an instance of StateStorage.');
				}

				this._multiContextOptions.stateStorage = options.stateStorage;
			}

			this._multiContextOptions.storeName = options.storeName;

			this._storageKey = this._storageNamespace + this._multiContextOptions.storeName;
			this._eventPrefix = this._eventNamespace + this._multiContextOptions.storeName + '::';
			this._mutationEventName = this._eventPrefix + 'mutation';
			this._contextUuid = Uuid.getV4();

			this.logger.warn('VuexManager: multi context mode enabled for store', this._multiContextOptions.storeName);

			return this;
		}

		/**
		 * Sets a mutation handler and subscribes to mutations from other JS-contexts
		 * if the multi context mode is enabled.
		 *
		 * @return {VuexManager}
		 */
		build()
		{
			this._mutation = new MutationManager();

			if (!this.isMultiContextMode)
			{
				this._unsubscribeStoreMutations = this._store.subscribe(this._mutation.getHandler());

				return this;
			}

			this._storage = new this._multiContextOptions.stateStorage({
				key: this._storageKey,
			});

			if (this._multiContextOptions.clearStateStorage)
			{
				this.clearStateStorage();
			}
			else
			{
				let state = this._storage.getState();
				if (state)
				{
					state = this._multiContextOptions.onBeforeReplaceState(state);

					this._store.replaceState(state);
				}
			}

			BX.addCustomEvent(this._mutationEventName, this._handleMutation);

			this._unsubscribeStoreMutations = this._store.subscribe(this._getMultiContextMutationHandler().bind(this));

			return this;
		}

		/**
		 * Unsubscribes from storage mutations and mutations from other contexts.
		 */
		disassemble()
		{
			this._unsubscribeStoreMutations();
			this._mutation = null;

			if (this.isMultiContextMode)
			{
				BX.removeCustomEvent(this._mutationEventName, this._handleMutation);
			}
		}

		on(mutationName, listener)
		{
			this._mutation.on(mutationName, listener);

			return this;
		}

		once(mutationName, listener)
		{
			this._mutation.once(mutationName, listener);

			return this;
		}

		off(mutationName, listener)
		{
			this._mutation.off(mutationName, listener);

			return this;
		}

		clearStateStorage()
		{
			this._storage.clearState();
		}

		_getMultiContextMutationHandler()
		{
			return (mutation, state) => {
				if (this._shouldPostMutation)
				{
					this._storage.setState(state);

					this._postMutation(mutation, this._multiContextOptions.shareState ? state : {});
				}

				this._mutation._handle(mutation, this._multiContextOptions.shareState ? state : {});
			};
		}

		_postMutation(mutation, state)
		{
			const eventData = {
				mutation,
				state,
				parentContextUuid: this._contextUuid,
			};

			BX.postComponentEvent(this._mutationEventName, [eventData]);
		}

		_onMutation(event)
		{
			const {
				mutation,
				state,
				parentContextUuid,
			} = event;

			if (parentContextUuid === this._contextUuid)
			{
				this.logger.log('VuexManager: ignore own mutation', mutation, state);

				return;
			}

			const mutationName = mutation.type;
			const payload = mutation.payload;

			this.logger.log('VuexManager: mutation received', mutation, state);

			//in order not to post mutations obtained from other contexts again
			this._shouldPostMutation = false;

			this._store.commit(mutationName, payload);

			this._shouldPostMutation = true;
		}
	}

	module.exports = { VuexManager };
});
