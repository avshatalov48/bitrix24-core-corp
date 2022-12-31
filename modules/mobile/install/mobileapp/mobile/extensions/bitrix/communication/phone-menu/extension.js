/**
 * @module communication/phone-menu
 */
jn.define('communication/phone-menu', (require, exports, module) => {

	const { Loc } = require('loc');

	/**
	 * @param {object} params
	 * @function OpenPhoneMenu
	 */
	function OpenPhoneMenu(params)
	{
		if (!params.number)
		{
			return;
		}

		const { number } = params;

		const items = [
			{ title: Loc.getMessage('PHONE_CALL'), code: 'callNativePhone' },
			{ title: Loc.getMessage('PHONE_CALL_B24'), code: 'callUseTelephony' },
			// { title: Loc.getMessage('PHONE_COPY'), code: 'copy' },
		];

		const callbacks = {
			callNativePhone: () => Application.openUrl(`tel:${number}`),
			callUseTelephony: () => BX.postComponentEvent('onPhoneTo', [params], 'calls'),
			// copy: () => Application.copyToClipboard(number),
		};

		dialogs.showActionSheet({
			items,
			callback: ({ code }) => {
				const callback = callbacks[code];

				if (typeof callback === 'function')
				{
					callback();
				}
			},
		});
	}

	module.exports = { OpenPhoneMenu };
});