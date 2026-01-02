/**
 * @module im/messenger/lib/element/dialog/message/custom/handler
 */
jn.define('im/messenger/lib/element/dialog/message/custom/handler', (require, exports, module) => {
	/**
	 * @class CustomMessageHandler
	 */
	class CustomMessageHandler
	{
		/**
		 * @param {IServiceLocator<MessengerLocatorServices>} serviceLocator
		 * @param {IServiceLocator<DialogLocatorServices>} dialogLocator
		 */
		constructor(serviceLocator, dialogLocator)
		{
			/**
			 * @protected
			 * @type {IServiceLocator<MessengerLocatorServices>}
			 */
			this.serviceLocator = serviceLocator;
			/**
			 * @protected
			 * @type {IServiceLocator<DialogLocatorServices>}
			 */
			this.dialogLocator = dialogLocator;

			this.bindMethods();
		}

		/**
		 * @return {void}
		 */
		destructor()
		{
			this.unsubscribeEvents();
		}

		/**
		 * @abstract
		 * @return {void}
		 */
		bindMethods()
		{
			throw new Error('CustomMessageHandler: bindMethods() must be override in subclass.');
		}

		/**
		 * @abstract
		 * @return {void}
		 */
		subscribeEvents()
		{
			throw new Error('CustomMessageHandler: subscribeEvents() must be override in subclass.');
		}

		/**
		 * @abstract
		 * @return {void}
		 */
		unsubscribeEvents()
		{
			throw new Error('CustomMessageHandler: unsubscribeEvents() must be override in subclass.');
		}

		/**
		 * @protected
		 * @param {string | number} messageId
		 * @return {*}
		 */
		getMetaDataByMessageId(messageId)
		{
			throw new Error('CustomMessageHandler: getMetaDataByMessageId() must be override in subclass.');
		}
	}

	module.exports = {
		CustomMessageHandler,
	};
});
