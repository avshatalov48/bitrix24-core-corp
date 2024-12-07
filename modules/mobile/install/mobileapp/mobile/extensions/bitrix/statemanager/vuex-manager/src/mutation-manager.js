/* eslint-disable flowtype/require-return-type */

/**
 * @module statemanager/vuex-manager/mutation-manager
 */
jn.define('statemanager/vuex-manager/mutation-manager', (require, exports, module) => {
	/**
	 * @class MutationManager
	 * Designed to subscribe to individual store mutations.
	 */
	class MutationManager
	{
		constructor()
		{
			/**
			 * @protected
			 */
			this.listenerCollection = new Map();
		}

		on(mutationName, listener)
		{
			if (typeof listener !== 'function')
			{
				throw new TypeError('MutationManager: listener must be a function');
			}

			if (!this.listenerCollection.has(mutationName))
			{
				this.listenerCollection.set(mutationName, []);
			}

			this.listenerCollection
				.get(mutationName)
				.push(listener)
			;
		}

		once(mutationName, listener)
		{
			const onetimeListener = (mutation, state) => {
				listener(mutation, state);
				this.off(mutationName, onetimeListener);
			};

			this.on(mutationName, onetimeListener);
		}

		off(mutationName, listener)
		{
			if (typeof listener !== 'function')
			{
				throw new TypeError('MutationManager: listener must be a function');
			}

			if (!this.listenerCollection.has(mutationName))
			{
				return;
			}

			const listenerCollection = this.listenerCollection
				.get(mutationName)
				.filter((handler) => handler !== listener)
			;

			this.listenerCollection.set(mutationName, listenerCollection);

			const hasNoListeners = this.listenerCollection.get(mutationName).length === 0;
			if (hasNoListeners)
			{
				this.listenerCollection.delete(mutationName);
			}
		}

		async handle(mutation = {}, state = {})
		{
			const mutationName = mutation.type;

			if (typeof mutationName !== 'string')
			{
				return;
			}

			if (!this.listenerCollection.has(mutationName))
			{
				return;
			}

			const mutationListeners = this.listenerCollection.get(mutationName);
			for (const listener of mutationListeners)
			{
				try
				{
					// eslint-disable-next-line no-await-in-loop
					await listener(mutation, state);
				}
				catch (error)
				{
					// eslint-disable-next-line no-console
					console.error('VuexManager: MutationManager.handle error', error, mutation, state);

					// throw new Error(error.message);
				}
			}
		}

		getHandler()
		{
			return this.handle.bind(this);
		}
	}

	module.exports = {
		MutationManager,
	};
});
