/**
 * @module stafftrack/data-managers/settings-manager
 */
jn.define('stafftrack/data-managers/settings-manager', (require, exports, module) => {
	const { FeatureAjax } = require('stafftrack/ajax');
	const { EventEmitter } = require('event-emitter');

	class SettingsManager extends EventEmitter
	{
		constructor()
		{
			super();

			this.setUid('Stafftrack.SettingsManager');
		}

		setEnabledBySettings(enabledBySettings)
		{
			this.enabledBySettings = enabledBySettings;
		}

		setGeoEnabled(geoEnabled)
		{
			this.geoEnabled = geoEnabled;
		}

		setTimemanAvailable(timemanAvailable)
		{
			this.timemanAvailable = timemanAvailable;
		}

		isEnabledBySettings()
		{
			return this.enabledBySettings;
		}

		isGeoEnabled()
		{
			return this.geoEnabled;
		}

		isTimemanAvailable()
		{
			return this.timemanAvailable;
		}

		turnCheckInSettingOn()
		{
			void FeatureAjax.turnCheckInSettingOn();

			this.setEnabledBySettings(true);

			this.emit('updated');
		}

		turnCheckInSettingOff()
		{
			void FeatureAjax.turnCheckInSettingOff();

			this.setEnabledBySettings(false);

			this.emit('updated');
		}

		turnCheckInGeoOn()
		{
			void FeatureAjax.turnCheckInGeoOn();

			this.setGeoEnabled(true);
		}

		turnCheckInGeoOff()
		{
			void FeatureAjax.turnCheckInGeoOff();

			this.setGeoEnabled(false);
		}
	}

	module.exports = { SettingsManager: new SettingsManager() };
});
