/**
 * @module im/messenger/lib/element/dialog/message/gallery/message
 */
jn.define('im/messenger/lib/element/dialog/message/gallery/message', (require, exports, module) => {
	const { CustomMessage } = require('im/messenger/lib/element/dialog/message/custom/message');

	/**
	 * @class GalleryMessage
	 */
	class GalleryMessage extends CustomMessage
	{
		/**
		 * @param {MessagesModelState} modelMessage
		 * @param {CreateMessageOptions} options
		 */
		constructor(modelMessage = {}, options = {})
		{
			super(modelMessage, options);
		}

		/**
		 * @description not used for fake gallery
		 * @return {string}
		 */
		static getComponentId()
		{
			return 'louvre-gallery';
		}

		/**
		 * @abstract
		 * @return {object | undefined}
		 */
		get metaData()
		{
			return {};
		}

		/**
		 * @abstract
		 * @return {string}
		 */
		getType()
		{
			throw new Error('Gallery: getType() must be override in subclass.');
		}
	}

	module.exports = {
		GalleryMessage,
	};
});
