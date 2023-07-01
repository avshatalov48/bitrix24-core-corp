/**
 * @module im/messenger/const/feature-flag
 */
jn.define('im/messenger/const/feature-flag', (require, exports, module) => {

	const { Type } = require('type');

	const FeatureFlag = Object.freeze({
		isDevelopmentEnvironment: (
			Application.getApiVersion() >= 44
			&& Application.isBeta()
			&& BX.componentParameters.get('IS_DEVELOPMENT_ENVIRONMENT')
		),
		isBetaVersion: Application.getApiVersion() >= 44 && Application.isBeta(),
		native: {
			imUtilsModuleSupported: Application.getApiVersion() >= 43,
			mediaModuleSupported: Application.getApiVersion() >= 43,
			openWebComponentParentWidgetSupported: Application.getApiVersion() >= 45,
		},
		list: {
			itemWillDisplaySupported: Application.getApiVersion() >= 43,
		},
		dialog: {
			nativeSupported: (
				Application.getApiVersion() >= 49
				&& Application.isBeta()
			),
		},
	});

	module.exports = {
		FeatureFlag,
	};
});
