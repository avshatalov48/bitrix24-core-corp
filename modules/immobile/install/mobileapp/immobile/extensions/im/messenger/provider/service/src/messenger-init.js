/**
 * @module im/messenger/provider/service/messenger-init
 */
jn.define('im/messenger/provider/service/messenger-init', (require, exports, module) => {
	const { runAction } = require('im/messenger/lib/rest');
	const { EventType } = require('im/messenger/const');

	/**
	 * @class MessengerInitService
	 */
	class MessengerInitService
	{
		/**
		 * @constructor
		 * @param {string} options.actionName
		 */
		constructor(options)
		{
			this.actionName = options.actionName;
			this.eventEmitter = new JNEventEmitter();
		}

		/**
		 * @param {string[]} methodList
		 */
		async runAction(methodList)
		{
			const data = { methodList };
			const result = await runAction(this.actionName, { data });

			this.eventEmitter.emit(EventType.messenger.init, [result]);
		}

		/**
		 * @param {Function} eventHandler
		 */
		onInit(eventHandler)
		{
			this.#on(EventType.messenger.init, eventHandler);
		}

		/**
		 * @param {Function} eventHandler
		 * @param {string} eventName
		 */
		#on(eventName, eventHandler)
		{
			this.eventEmitter.on(eventName, eventHandler);
		}
	}

	module.exports = {
		MessengerInitService,
	};
});
