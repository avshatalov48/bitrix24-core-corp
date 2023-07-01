/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

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
		 */
		static emit(eventName, eventData)
		{
			if (!Type.isStringFilled(eventName))
			{
				throw new Error('MessengerEvent: ' + eventName + 'is not a filled string');
			}

			BX.postComponentEvent(eventName, [ eventData ], MessengerParams.get('COMPONENT_CODE'));
		}
	}

	module.exports = {
		MessengerEmitter,
	};
});
