/**
 * @module im/messenger/lib/element/dialog/message/call/message
 */
jn.define('im/messenger/lib/element/dialog/message/call/message', (require, exports, module) => {
	const { CustomMessage } = require('im/messenger/lib/element/dialog/message/custom/message');
	const { CallMessageConfiguration } = require('im/messenger/lib/element/dialog/message/call/configuration');
	const { MessageParams } = require('im/messenger/const');


	/**
	 * @class CallMessage
	 */
	class CallMessage extends CustomMessage
	{
		/**
		 * @param {MessagesModelState} modelMessage
		 * @param {CreateMessageOptions} options
		 */
		constructor(modelMessage, options = {})
		{
			super(modelMessage, options);

			this.call = {
				title: '',
				description: '',
				iconName: '',
				iconColor: '',
				iconFallbackUrl: '',
				iconBorderColor: '',
			};

			this.createIconData(modelMessage)
				.createTitleData()
				.createDescriptionData()
			;
		}

		static getComponentId()
		{
			return MessageParams.ComponentId.CallMessage;
		}

		getType()
		{
			return 'call';
		}

		get metaData()
		{
			const configuration = new CallMessageConfiguration(this.id);

			return configuration.getMetaData();
		}

		createIconData(modelMessage)
		{
			const iconColors = this.metaData.iconColors(modelMessage);

			this.call.iconName = this.metaData.iconName;
			this.call.iconColor = iconColors.iconColor;
			this.call.iconFallbackUrl = this.metaData.iconFallbackUrl;
			this.call.iconBorderColor = iconColors.iconBorderColor;

			return this;
		}

		createTitleData()
		{
			this.call.title = this.getComponentParams().messageText;

			return this;
		}

		createDescriptionData()
		{
			this.call.description = this.time;

			return this;
		}
	}

	module.exports = {
		CallMessage,
	};
});
