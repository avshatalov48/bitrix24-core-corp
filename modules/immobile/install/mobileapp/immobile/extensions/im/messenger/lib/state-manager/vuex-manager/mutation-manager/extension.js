/**
 * @module im/messenger/lib/state-manager/vuex-manager/mutation-manager
 */
jn.define('im/messenger/lib/state-manager/vuex-manager/mutation-manager', (require, exports, module) => {
	const { MutationManager } = require('statemanager/vuex-manager');

	const { MessengerMutationManagerEvent } = require('im/messenger/lib/state-manager/vuex-manager/const');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('core--messenger-mutation-manager');

	/**
	 * @class MessengerMutationManager
	 */
	class MessengerMutationManager extends MutationManager
	{
		async handle(mutation = {}, state = {})
		{
			await super.handle(mutation, state);

			if (this.checkPostCompleteByMutation(mutation))
			{
				logger.log('MessengerMutationManager: handlers are executed for', mutation);
				this.postCompleteEvent(mutation, state);
			}
		}

		/**
		 * @param {Object} mutation
		 * @return {Boolean}
		 */
		checkPostCompleteByMutation(mutation)
		{
			if (mutation?.type.includes('messagesModel'))
			{
				return true;
			}

			return false;
		}

		/**
		 * @param {Object} mutation
		 * @param {Object} state
		 * @void
		 */
		postCompleteEvent(mutation, state)
		{
			BX.postComponentEvent(MessengerMutationManagerEvent.handleComplete, [mutation, state]);
		}
	}

	module.exports = {
		MessengerMutationManager,
	};
});
