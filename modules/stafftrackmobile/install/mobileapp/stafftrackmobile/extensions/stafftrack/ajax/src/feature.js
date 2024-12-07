/**
 * @module stafftrack/ajax/feature
 */
jn.define('stafftrack/ajax/feature', (require, exports, module) => {
	const { BaseAjax } = require('stafftrack/ajax/base');

	const FeatureAction = {
		TURN_CHECK_IN_SETTING_ON: 'turnCheckInSettingOn',
		TURN_CHECK_IN_SETTING_OFF: 'turnCheckInSettingOff',
		TURN_CHECK_IN_GEO_ON: 'turnCheckInGeoOn',
		TURN_CHECK_IN_GEO_OFF: 'turnCheckInGeoOff',
		CREATE_DEPARTMENT_HEAD_CHAT: 'createDepartmentHeadChat',
	};

	class FeatureAjax extends BaseAjax
	{
		/**
		 * @returns {string}
		 */
		getEndpoint()
		{
			return 'stafftrack.Feature';
		}

		turnCheckInSettingOn()
		{
			return this.fetch(FeatureAction.TURN_CHECK_IN_SETTING_ON);
		}

		turnCheckInSettingOff()
		{
			return this.fetch(FeatureAction.TURN_CHECK_IN_SETTING_OFF);
		}

		turnCheckInGeoOn()
		{
			return this.fetch(FeatureAction.TURN_CHECK_IN_GEO_ON);
		}

		turnCheckInGeoOff()
		{
			return this.fetch(FeatureAction.TURN_CHECK_IN_GEO_OFF);
		}

		createDepartmentHeadChat(featureName)
		{
			return this.fetch(FeatureAction.CREATE_DEPARTMENT_HEAD_CHAT, { featureName });
		}
	}

	module.exports = { FeatureAjax: new FeatureAjax() };
});
