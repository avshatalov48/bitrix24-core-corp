/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-pseudo-private */

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
			this._listenerCollection = new Map();
		}

		on(mutationName, listener)
		{
			if (typeof listener !== 'function')
			{
				throw new Error('MutationManager: listener must be a function');
			}

			if (!this._listenerCollection.has(mutationName))
			{
				this._listenerCollection.set(mutationName, []);
			}

			this._listenerCollection
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
				throw new Error('MutationManager: listener must be a function');
			}

			if (!this._listenerCollection.has(mutationName))
			{
				return;
			}

			const listenerCollection =
				this._listenerCollection
					.get(mutationName)
					.filter(handler => handler !== listener)
			;

			this._listenerCollection.set(mutationName, listenerCollection);

			const hasNoListeners = this._listenerCollection.get(mutationName).length === 0;
			if (hasNoListeners)
			{
				this._listenerCollection.delete(mutationName);
			}
		}

		_handle(mutation = {}, state = {})
		{
			const mutationName = mutation.type;

			if (typeof mutationName !== 'string')
			{
				return;
			}

			if (!this._listenerCollection.has(mutationName))
			{
				return;
			}

			this._listenerCollection
				.get(mutationName)
				.forEach(listener => listener(mutation, state))
			;
		}

		getHandler()
		{
			return this._handle.bind(this);
		}
	}

	module.exports = { MutationManager };
});
