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

		static isGridViewSupported()
		{
			return Application.getApiVersion() >= 47;
		}

		static isImageSupportsSuccessCallback()
		{
			return Application.getApiVersion() >= 47;
		}

		static isKeyboardEventsSupported()
		{
			return Application.getApiVersion() >= 48;
		}

		static isOAuthSupported()
		{
			return Application.getApiVersion() >= 48;
		}

		static isReceivePaymentSupported()
		{
			return Application.getApiVersion() >= 49;
		}

		static showDefaultUnsupportedWidget(props = {}, parentWidget = PageManager)
		{
			AppUpdateNotifier.open(props, parentWidget);
		}
	}

	module.exports = { Feature };

});
