/**
 * @module im/messenger/view/lib/state-manager
 */
jn.define('im/messenger/view/lib/state-manager', (require, exports, module) => {
	const { isEqual } = require('utils/object');

	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('view--state-manager');

	/**
	 * @class StateManager
	 */
	class StateManager
	{
		#state;

		/**
		 * @constructor
		 * @param {object} state
		 */
		constructor(state)
		{
			this.#state = state;
		}

		get state()
		{
			return this.#state;
		}

		/**
		 * @param {object} newState
		 * @returns {boolean}
		 */
		hasChanges(newState = {})
		{
			const allStateKeys = new Set([
				...Object.keys(this.#state),
				...Object.keys(newState),
			]);

			const hasChanges = [...allStateKeys].some((key) => !isEqual(this.#state[key], newState[key]));
			if (hasChanges)
			{
				logger.log(`${this.constructor.name}.hasChanges state changed, oldState: `, this.#state, 'newState:', newState);
			}
			else
			{
				logger.log(`${this.constructor.name}.hasChanges state unchanged, currentState: `, this.#state, 'newState:', newState);
			}

			return hasChanges;
		}

		/**
		 * @param {object} newState
		 */
		updateState(newState = {})
		{
			this.#state = { ...this.#state, ...newState };
		}
	}

	module.exports = {
		StateManager,
	};
});
