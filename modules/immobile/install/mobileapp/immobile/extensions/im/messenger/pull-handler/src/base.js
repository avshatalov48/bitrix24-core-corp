/* eslint-disable bitrix-rules/no-pseudo-private */
/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-bx-message */

/**
 * @module im/messenger/pull-handler/base
 */
jn.define('im/messenger/pull-handler/base', (require, exports, module) => {

	const { MessengerEvent } = jn.require('im/messenger/lib/event');

	/**
	 * @class PullHandler
	 */
	class PullHandler
	{
		constructor(options = {})
		{
		}

		getModuleId()
		{
			return 'im';
		}

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
	}

	module.exports = {
		PullHandler,
	};
});
