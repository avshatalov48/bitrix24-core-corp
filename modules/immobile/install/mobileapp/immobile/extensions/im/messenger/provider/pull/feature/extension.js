/**
 * @module im/messenger/provider/pull/feature
 */
jn.define('im/messenger/provider/pull/feature', (require, exports, module) => {
	const { BasePullHandler } = require('im/messenger/provider/pull/base/pull-handler');
	const { Feature } = require('im/messenger/lib/feature');

	/**
	 * @class FeaturePullHandler
	 */
	class FeaturePullHandler extends BasePullHandler
	{
		/**
		 * @param {UpdateFeatureParams} params
		 */
		handleUpdateFeature(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			const data = { [params.name]: params.value };
			Feature.updateExistingImFeatures(data);
		}
	}

	module.exports = {
		FeaturePullHandler,
	};
});
