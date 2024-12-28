/**
 * @module qrauth/utils/src/manager
 */
jn.define('qrauth/utils/src/manager', (require, exports, module) => {
	const { Loc } = require('loc');

	// eslint-disable-next-line no-undef
	const componentUrl = availableComponents.qrcodeauth?.publicUrl;

	/**
	 * @param {QRCodeAuthProps} params
	 * @returns {Promise<void>}
	 */
	async function openManager(params = {})
	{
		const { title, layout, external, ...restParams } = params;
		const parentLayout = layout && layout !== PageManager ? layout : null;

		if (!external && parentLayout)
		{
			const { QRCodeAuthComponent } = await requireLazy('qrauth') || {};

			return QRCodeAuthComponent?.open?.(parentLayout, { ...restParams, title });
		}

		const pageManagerHeight = 600;

		PageManager.openComponent('JSStackComponent', {
			scriptPath: componentUrl,
			canOpenInDefault: true,
			componentCode: 'qrcodeauth',
			params: {
				external,
				...restParams,
			},
			rootWidget: {
				name: 'layout',
				settings: {
					objectName: 'layout',
					titleParams: {
						text: title || Loc.getMessage('LOGIN_ON_DESKTOP_DEFAULT_TITLE_MSGVER_3'),
						type: 'dialog',
					},
					backdrop: {
						bounceEnable: true,
						mediumPositionHeight: pageManagerHeight,
					},
				},
			},
		}, parentLayout);

		return Promise.resolve();
	}

	module.exports = { openManager };
});
