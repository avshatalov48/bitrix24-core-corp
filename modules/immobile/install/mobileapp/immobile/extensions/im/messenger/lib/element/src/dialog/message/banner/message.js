/**
 * @module im/messenger/lib/element/dialog/message/banner/message
 */
jn.define('im/messenger/lib/element/dialog/message/banner/message', (require, exports, module) => {
	const { CustomMessage } = require('im/messenger/lib/element/dialog/message/custom/message');
	const { MessageType } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { BannerMessageConfiguration } = require('im/messenger/lib/element/dialog/message/banner/configuration');

	/**
	 * @class BannerMessage
	 */
	class BannerMessage extends CustomMessage
	{
		constructor(modelMessage = {}, options = {})
		{
			super(modelMessage, options);
			/** @type {BannerProps} */
			this.banner = {};

			this.setMessage(modelMessage.text);
			this.prepareTextMessage();
			this.setBannerProp();
			this.setAvatarUri(null);
		}

		/**
		 * @return {CoreApplication}
		 */
		getCore()
		{
			return serviceLocator.get('core');
		}

		/**
		 * @abstract
		 * @void
		 */
		prepareTextMessage()
		{}

		get metaData()
		{
			const configuration = new BannerMessageConfiguration(this.id);

			return configuration.getMetaData();
		}

		setBannerProp()
		{
			this.banner = this.metaData?.banner;
		}

		/**
		 * @return {string}
		 */
		getType()
		{
			return MessageType.banner;
		}

		static getComponentId()
		{
			return 'banner';
		}
	}

	module.exports = {
		BannerMessage,
	};
});
