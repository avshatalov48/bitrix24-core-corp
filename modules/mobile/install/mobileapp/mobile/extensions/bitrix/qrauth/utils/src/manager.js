/**
 * @module qrauth/utils/src/manager
 */
jn.define('qrauth/utils/src/manager', (require, exports, module) => {
	const { Loc } = require('loc');
	const { getMediumHeight } = require('utils/page-manager');

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

		let pageManagerHeight = 600;

		if (!external)
		{
			const { QRCodeAuthComponent } = await requireLazy('qrauth') || {};

			pageManagerHeight = QRCodeAuthComponent.getContentHeight(restParams);

			if (parentLayout)
			{
				return QRCodeAuthComponent?.open?.(parentLayout, { ...restParams, title });
			}
		}

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
						mediumPositionHeight: getMediumHeight({ height: pageManagerHeight }),
					},
				},
			},
		}, parentLayout);

		return Promise.resolve();
	}

	module.exports = { openManager };
});
