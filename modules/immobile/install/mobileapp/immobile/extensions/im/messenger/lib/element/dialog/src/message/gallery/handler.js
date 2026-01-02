/**
 * @module im/messenger/lib/element/dialog/message/gallery/handler
 */
jn.define('im/messenger/lib/element/dialog/message/gallery/handler', (require, exports, module) => {
	const { CustomMessageHandler } = require('im/messenger/lib/element/dialog/message/custom/handler');

	/**
	 * @class GalleryMessageHandler
	 */
	class GalleryMessageHandler extends CustomMessageHandler
	{
		/**
		 * @abstract
		 * @return {void}
		 */
		bindMethods()
		{
			throw new Error('GalleryMessageHandler: bindMethods() must be override in subclass.');
		}

		/**
		 * @abstract
		 * @return {void}
		 */
		subscribeEvents()
		{
			throw new Error('GalleryMessageHandler: subscribeEvents() must be override in subclass.');
		}

		/**
		 * @abstract
		 * @return {void}
		 */
		unsubscribeEvents()
		{
			throw new Error('GalleryMessageHandler: unsubscribeEvents() must be override in subclass.');
		}
	}

	module.exports = {
		GalleryMessageHandler,
	};
});
