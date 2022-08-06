/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/lib/event-manager/event-manager
 */
jn.define('im/messenger/lib/event-manager/event-manager', (require, exports, module) => {

	const { Type } = jn.require('type');

	/**
	 * @class EventManager
	 */
	class EventManager
	{
		constructor()
		{
			this.eventHandlersList = new Map();
		}

		on(eventName, eventHandler)
		{
			if (!Type.isFunction(eventHandler))
			{
				throw new Error('EventManager: eventHandler must be a function');
			}

			if (!this.eventHandlersList.has(eventName))
			{
				this.eventHandlersList.set(eventName, []);
			}

			this.eventHandlersList.get(eventName).push(eventHandler);
		}

		off(eventName, eventHandler)
		{
			if (!Type.isFunction(eventHandler))
			{
				throw new Error('EventManager: eventHandler must be a function');
			}

			if (!this.eventHandlersList.has(eventName))
			{
				return;
			}

			const handlerList = this.eventHandlersList.get(eventName).filter(handler => handler !== eventHandler);
			this.eventHandlersList.set(eventName, handlerList);

			if (this.eventHandlersList.get(eventName).size === 0)
			{
				this.eventHandlersList.delete(eventName);
			}
		}

		emit(eventName, eventData)
		{
			if (!this.eventHandlersList.has(eventName))
			{
				return;
			}

			this.eventHandlersList.get(eventName).forEach(eventHandler => eventHandler(eventData));
		}

		getEventHandler()
		{
			return this.emit.bind(this);
		}
	}

	module.exports = { EventManager };
});
