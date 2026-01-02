/**
 * @module im/messenger/lib/element/dialog/message/check-in/configuration
 */
jn.define('im/messenger/lib/element/dialog/message/check-in/configuration', (require, exports, module) => {
	const { Type } = require('type');
	const { CustomMessageConfiguration } = require('im/messenger/lib/element/dialog/message/custom/configuration');
	const { CheckInType } = require('im/messenger/lib/element/dialog/message/check-in/const/type');
	const { metaData } = require('im/messenger/lib/element/dialog/message/check-in/const/configuration');

	/**
	 * @class CheckInMessageConfiguration
	 */
	class CheckInMessageConfiguration extends CustomMessageConfiguration
	{
		constructor(messageId)
		{
			super(messageId, metaData);
		}

		/**
		 * @protected
		 * @return {$Values<CheckInType>}
		 */
		getMetaDataKey()
		{
			const componentParams = this.getMessageComponentParams();
			if (Type.isObject(componentParams) && Type.isStringFilled(componentParams?.location))
			{
				return CheckInType.withLocation;
			}

			return CheckInType.withoutLocation;
		}

		/**
		 * @return {CheckInMetaDataValue}
		 */
		getMetaData()
		{
			return super.getMetaData();
		}
	}

	module.exports = {
		CheckInMessageConfiguration,
	};
});
