/**
 * @module communication/events/phone
 */
jn.define('communication/events/phone', (require, exports, module) => {
	const { BaseEvent } = require('communication/events/base');
	const { openPhoneMenu } = require('communication/phone-menu');

	class PhoneEvent extends BaseEvent
	{
		open()
		{
			if (this.isEmpty())
			{
				return;
			}

			openPhoneMenu(this.getCallParameters());
		}

		getCallParameters()
		{
			const {
				number,
				layoutWidget,
				params = {}
			} = this.getValue();

			return {
				number,
				canUseTelephony: this.canUseTelephony(),
				layoutWidget,
				params: {
					...params,
					AUTO_FOLD: true,
				},
			};
		}

		canUseTelephony()
		{
			return BX.prop.getBoolean(
				jnExtensionData.get('communication/events'),
				'canUseTelephony',
				false,
			);
		}
	}

	module.exports = { PhoneEvent };
});
