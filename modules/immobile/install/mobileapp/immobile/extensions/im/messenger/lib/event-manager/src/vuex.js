/**
 * @module im/messenger/lib/event-manager/vuex
 */
jn.define('im/messenger/lib/event-manager/vuex', (require, exports, module) => {

	const { Type } = jn.require('im/messenger/lib/core');
	const { EventManager } = jn.require('im/messenger/lib/event-manager/event-manager');

	/**
	 * @class VuexEventManager
	 */
	class VuexEventManager extends EventManager
	{
		emit(mutation = {}, state = {})
		{
			const eventName = mutation.type;

			if (!Type.isString(eventName))
			{
				return;
			}

			if (!this.eventHandlersList.has(eventName))
			{
				return;
			}

			this.eventHandlersList.get(eventName).forEach(eventHandler => eventHandler(mutation, state));
		}
	}

	module.exports = { VuexEventManager };
});
