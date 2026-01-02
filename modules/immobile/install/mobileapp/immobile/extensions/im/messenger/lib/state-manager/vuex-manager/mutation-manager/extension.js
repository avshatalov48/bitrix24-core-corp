/**
 * @module im/messenger/lib/state-manager/vuex-manager/mutation-manager
 */
jn.define('im/messenger/lib/state-manager/vuex-manager/mutation-manager', (require, exports, module) => {
	const { MutationManager } = require('statemanager/vuex-manager');

	const { MessengerMutationManagerEvent } = require('im/messenger/lib/state-manager/vuex-manager/const');
	const { getLogger } = require('im/messenger/lib/logger');
	const logger = getLogger('core--messenger-mutation-manager');

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
				// don't send state in the event, this overloads the app
				this.postCompleteEvent(mutation);
			}
		}

		/**
		 * @param {Object} mutation
		 * @return {Boolean}
		 */
		checkPostCompleteByMutation(mutation)
		{
			const availableMutationsName = ['messagesModel'];
			if (availableMutationsName.some((modelName) => mutation?.type.includes(modelName)))
			{
				return true;
			}

			return false;
		}

		/**
		 * @param {Object} mutation
		 * @void
		 */
		postCompleteEvent(mutation)
		{
			BX.postComponentEvent(MessengerMutationManagerEvent.handleComplete, [mutation]);
		}
	}

	module.exports = {
		MessengerMutationManager,
	};
});
