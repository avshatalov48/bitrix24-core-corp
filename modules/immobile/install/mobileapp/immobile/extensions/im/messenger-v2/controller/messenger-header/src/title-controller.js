/**
 * @module im/messenger-v2/controller/messenger-header/title-controller
 */
jn.define('im/messenger-v2/controller/messenger-header/title-controller', (require, exports, module) => {
	const { Loc } = require('im/messenger/loc');
	const { isEqual } = require('utils/object');

	const { AppStatus } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	/**
	 * @class HeaderTitleController
	 */
	class HeaderTitleController
	{
		#ui;

		constructor(widget)
		{
			this.#ui = widget;
			/**
			 * @private
			 * @type {JNWidgetTitleParams}
			 */
			this.titleParams = null;
		}

		/**
		 * @return {CoreApplication}
		 */
		get #core()
		{
			return serviceLocator.get('core');
		}

		redrawTitleIfNeeded()
		{
			const appStatus = this.#core.getAppStatus();
			const titleParams = this.getTitleParamsByAppStatus(appStatus);
			if (isEqual(this.titleParams, titleParams))
			{
				return;
			}

			this.redrawTitle(titleParams);
		}

		/**
		 * @private
		 * @param {string} appStatus
		 * @return {JNWidgetTitleParams}
		 */
		getTitleParamsByAppStatus(appStatus)
		{
			let headerTitle = '';
			let useProgress = '';

			switch (appStatus)
			{
				case AppStatus.networkWaiting:
					headerTitle = Loc.getMessage('IMMOBILE_MESSENGER_HEADER_NETWORK_WAITING');
					useProgress = true;
					break;

				case AppStatus.connection:
					headerTitle = Loc.getMessage('IMMOBILE_MESSENGER_HEADER_CONNECTION');
					useProgress = true;
					break;

				case AppStatus.sync:
					headerTitle = Loc.getMessage('IMMOBILE_MESSENGER_HEADER_SYNC');
					useProgress = true;
					break;

				default:
					headerTitle = Loc.getMessage('IMMOBILE_MESSENGER_HEADER_DEFAULT');
					useProgress = false;
					break;
			}

			return {
				text: headerTitle,
				useProgress,
				largeMode: true,
			};
		}

		/**
		 * @private
		 * @param {JNWidgetTitleParams} titleParams
		 */
		redrawTitle(titleParams)
		{
			this.titleParams = titleParams;
			this.#ui.setTitle(this.titleParams);
		}
	}

	module.exports = { HeaderTitleController };
});
