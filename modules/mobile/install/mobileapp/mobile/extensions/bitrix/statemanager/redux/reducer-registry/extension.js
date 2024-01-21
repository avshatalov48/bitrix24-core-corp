/**
 * @module statemanager/redux/reducer-registry
 */
jn.define('statemanager/redux/reducer-registry', (require, exports, module) => {
	/**
	 * @class ReducerRegistry
	 */
	class ReducerRegistry
	{
		constructor()
		{
			this.emitChange = null;
			this.reducers = new Map();
		}

		getReducers()
		{
			return Object.fromEntries(this.reducers);
		}

		/**
		 * @public
		 * @param {string} name
		 * @param {object} reducer
		 */
		register(name, reducer)
		{
			if (this.reducers.has(name))
			{
				return;
			}

			this.reducers.set(name, reducer);

			if (this.emitChange)
			{
				this.emitChange(this.getReducers());
			}
		}

		setChangeListener(listener)
		{
			this.emitChange = listener;
		}
	}

	module.exports = {
		ReducerRegistry: new ReducerRegistry(),
	};
});
