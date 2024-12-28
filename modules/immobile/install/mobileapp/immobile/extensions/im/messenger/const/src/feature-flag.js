/**
 * @module im/messenger/const/feature-flag
 */
jn.define('im/messenger/const/feature-flag', (require, exports, module) => {

	/**
	 * @deprecated
	 * @see Feature
	 */
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
			utilsSaveToLibrarySupported: Application.getApiVersion() >= 50,
		},
		list: {
			itemWillDisplaySupported: Application.getApiVersion() >= 43,
		},
		dialog: {
			nativeSupported: (
				Application.getApiVersion() >= 50
			),
		},
	});

	module.exports = {
		FeatureFlag,
	};
});
