/**
 * @module im/messenger/lib/element/dialog/message/call/configuration
 */
jn.define('im/messenger/lib/element/dialog/message/call/configuration', (require, exports, module) => {
	const { CustomMessageConfiguration } = require('im/messenger/lib/element/dialog/message/custom/configuration');
	const { CallMessageType } = require('im/messenger/lib/element/dialog/message/call/const/type');
	const { metaData } = require('im/messenger/lib/element/dialog/message/call/const/configuration');

	/**
	 * @class CallMessageConfiguration
	 */
	class CallMessageConfiguration extends CustomMessageConfiguration
	{
		constructor(messageId)
		{
			super(messageId, metaData);
		}

		/**
		 * @protected
		 * @return {$Values<CallMessageType>}
		 */
		getMetaDataKey()
		{
			const componentParams = this.getMessageComponentParams();

			return componentParams.messageType;
		}

		/**
		 * @return {CallMetaDataValue}
		 */
		getMetaData()
		{
			return super.getMetaData();
		}
	}

	module.exports = {
		CallMessageConfiguration,
	};
});
