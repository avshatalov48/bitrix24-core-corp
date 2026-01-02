/**
 * @module feature-flag
 */
jn.define('feature-flag', (require, exports, module) => {
	const { RunActionExecutor } = require('rest/run-action-executor');

	const FeatureFlagType = {
		SETTINGS_V2: 'SettingsV2Feature',
		SUPPORT: 'SupportFeature',
		WHATS_NEW: 'WhatsNewFeature',
		DEVELOPER_MENU: 'DeveloperMenuEnabled',
	};

	function getFeatureFlags()
	{
		const executor = new RunActionExecutor('mobile.FeatureFlag.getFeatureFlags');
		executor
			.setCacheId('FeatureFlags')
			.setCacheTtl(86400)
			.setCacheHandler(() => {})
			.enableJson()
			.setSkipRequestIfCacheExists();

		return executor.call(true);
	}

	async function checkFeatureFlag(featureFlag)
	{
		if (!Object.values(FeatureFlagType).includes(featureFlag))
		{
			console.error(`Invalid feature flag requested. \n Expected one of: ${Object.values(FeatureFlagType).join(', ')}. Received: "${featureFlag}"`);
		}

		const response = await getFeatureFlags();

		return response.data[featureFlag];
	}

	module.exports = {
		FeatureFlagType,
		getFeatureFlags,
		checkFeatureFlag,
	};
});
