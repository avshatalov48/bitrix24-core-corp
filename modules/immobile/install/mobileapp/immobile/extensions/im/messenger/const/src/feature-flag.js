/**
 * @module im/messenger/const/feature-flag
 */
jn.define('im/messenger/const/feature-flag', (require, exports, module) => {

	const FeatureFlag = Object.freeze({
		isBetaVersion: Application.getApiVersion() >= 44 && Application.isBeta(),
		native: {
			imUtilsModuleSupported: Application.getApiVersion() >= 43,
		},
		list: {
			itemWillDisplaySupported: Application.getApiVersion() >= 43,
		},
		dialog: {
			nativeSupported: Application.getApiVersion() >= 43,
		},
	});

	module.exports = {
		FeatureFlag,
	};
});
