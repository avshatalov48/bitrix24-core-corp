/**
 * @module im/messenger/controller/base
 */
jn.define('im/messenger/controller/base', (require, exports, module) => {

	const { MessengerEvent } = jn.require('im/messenger/lib/event');

	/**
	 * @class Controller
	 */
	class Controller
	{
		/**
		 * Send event to root messenger component
		 *
		 * @param {string} eventName
		 * @param {Object} [eventData]
		 */
		emitMessengerEvent(eventName, eventData)
		{
			new MessengerEvent(eventName, eventData).send();
		}

		/**
		 * Subscribes to the messenger event
		 *
		 * @param {string} eventName
		 * @param {function} eventHandler
		 */
		onMessengerEvent(eventName, eventHandler)
		{
			BX.addCustomEvent(eventName, eventHandler);
		}
	}

	module.exports = { Controller };
});
