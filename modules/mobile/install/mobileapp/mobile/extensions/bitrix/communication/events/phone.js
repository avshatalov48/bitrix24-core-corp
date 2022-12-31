/**
 * @module communication/events/phone
 */
jn.define('communication/events/phone', (require, exports, module) => {

	const { isEmpty } = require('utils/object');
	const { BaseEvent } = require('communication/events/base');
	const { OpenPhoneMenu } = require('communication/phone-menu');

	class PhoneEvent extends BaseEvent
	{
		open()
		{
			if (this.isEmpty())
			{
				return;
			}

			const params = this.getCallParameters();

			if (this.canUseTelephony())
			{
				OpenPhoneMenu(params);
			}
			else
			{
				this.callUsingNativePhone(params.number);
			}
		}

		getCallParameters()
		{
			const { number, params = {} } = this.getValue();

			return {
				number,
				params: {
					...params,
					AUTO_FOLD: true,
				},
			};
		}

		callUsingNativePhone(number)
		{
			if (!number)
			{
				return;
			}

			Application.openUrl(`tel:${number}`);
		}

		callUsingTelephony(params)
		{
			const callParameters = !isEmpty(params)
				? params
				: this.getCallParameters();

			BX.postComponentEvent(
				'onPhoneTo',
				[callParameters],
				'calls',
			);
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