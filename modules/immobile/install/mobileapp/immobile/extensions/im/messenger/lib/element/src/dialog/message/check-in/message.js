/**
 * @module im/messenger/lib/element/dialog/message/check-in/message
 */
jn.define('im/messenger/lib/element/dialog/message/check-in/message', (require, exports, module) => {
	const { Type } = require('type');

	const {
		MessageType,
		MessageParams,
	} = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { CustomMessage } = require('im/messenger/lib/element/dialog/message/custom/message');
	const { CheckInMessageConfiguration } = require('im/messenger/lib/element/dialog/message/check-in/configuration');

	/**
	 * @class CheckInMessage
	 */
	class CheckInMessage extends CustomMessage
	{
		/**
		 * @param {MessagesModelState} modelMessage
		 * @param {CreateMessageOptions} options
		 */
		constructor(modelMessage, options = {})
		{
			super(modelMessage, options);

			this.checkIn = {
				imageUrl: '',
				chipsText: null,
				addressText: null,
				buttonText: '',
			};

			this
				.createImageUrl()
				.createChipsText()
				.createAddressText()
				.createButtonText()
			;
		}

		static getComponentId()
		{
			return MessageParams.ComponentId.CheckInMessage;
		}

		getType()
		{
			return MessageType.checkIn;
		}

		get metaData()
		{
			const configuration = new CheckInMessageConfiguration(this.id);

			return configuration.getMetaData();
		}

		createImageUrl()
		{
			const host = serviceLocator.get('core').getHost();
			const imageUrl = this.getComponentParams().url;
			if (Type.isStringFilled(imageUrl))
			{
				this.checkIn.imageUrl = imageUrl.startsWith('/') ? host + imageUrl : imageUrl;
			}

			return this;
		}

		createChipsText()
		{
			const status = this.getComponentParams().status;
			if (Type.isStringFilled(status))
			{
				this.checkIn.chipsText = status;
			}

			return this;
		}

		createAddressText()
		{
			const location = this.getComponentParams().location;
			if (Type.isStringFilled(location))
			{
				this.checkIn.addressText = location;
			}

			return this;
		}

		createButtonText()
		{
			this.checkIn.buttonText = this.metaData.button.text;

			return this;
		}
	}

	module.exports = {
		CheckInMessage,
	};
});
