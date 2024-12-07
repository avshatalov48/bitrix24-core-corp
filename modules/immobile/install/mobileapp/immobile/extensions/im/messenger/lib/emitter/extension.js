/**
 * @module im/messenger/lib/emitter
 */
jn.define('im/messenger/lib/emitter', (require, exports, module) => {
	const { Type } = require('type');
	const { MessengerParams } = require('im/messenger/lib/params');

	class MessengerEmitter
	{
		/**
		 * Send event to root messenger component
		 *
		 * @param {string} eventName
		 * @param {Object} [eventData]
		 * @param {string|null} [componentCode=null]
		 */
		static emit(eventName, eventData, componentCode = null)
		{
			if (!Type.isStringFilled(eventName))
			{
				throw new Error(`MessengerEvent: ${eventName}is not a filled string`);
			}

			const component = componentCode || MessengerParams.get('COMPONENT_CODE');
			BX.postComponentEvent(eventName, [eventData], component);
		}

		/**
		 * @desc Send event to all contexts
		 *
		 * @param {string} eventName
		 * @param {Object} [eventData]
		 */
		static broadcast(eventName, eventData)
		{
			if (!Type.isStringFilled(eventName))
			{
				throw new Error(`MessengerEvent: ${eventName}is not a filled string`);
			}

			BX.postComponentEvent(eventName, [eventData]);
		}
	}

	module.exports = {
		MessengerEmitter,
	};
});
