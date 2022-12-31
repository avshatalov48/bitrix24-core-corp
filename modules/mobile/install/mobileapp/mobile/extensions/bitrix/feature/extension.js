/**
 * @module feature
 */
jn.define('feature', (require, exports, module) => {

	const { AppUpdateNotifier } = require('app-update-notifier');

	/**
	 * @class Feature
	 */
	class Feature
	{
		static isShareDialogSupportsFiles()
		{
			return Application.getApiVersion() >= 47;
		}

		static showDefaultUnsupportedWidget()
		{
			AppUpdateNotifier.open();
		}
	}

	module.exports = { Feature };

});