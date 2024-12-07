/**
 * @module im/messenger/lib/element/dialog/message/banner/configuration
 */
jn.define('im/messenger/lib/element/dialog/message/banner/configuration', (require, exports, module) => {
	const { Type } = require('type');
	const { CustomMessageConfiguration } = require('im/messenger/lib/element/dialog/message/custom/configuration');
	const { metaData } = require('im/messenger/lib/element/dialog/message/banner/const/configuration');

	/**
	 * @class BannerMessageConfiguration
	 */
	class BannerMessageConfiguration extends CustomMessageConfiguration
	{
		constructor(messageId)
		{
			super(messageId, metaData);
		}

		/**
		 * @protected
		 * @return {string | null}
		 */
		getMetaDataKey()
		{
			const componentId = this.getMessageComponentId();
			if (Type.isStringFilled(componentId))
			{
				return componentId;
			}

			return null;
		}

		/**
		 * @param {?string|null} [key=null]
		 * @return {BannerMetaDataValue}
		 */
		getMetaData(key)
		{
			return super.getMetaData(key);
		}
	}

	module.exports = {
		BannerMessageConfiguration,
	};
});
