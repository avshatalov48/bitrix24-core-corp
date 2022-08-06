/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/pull-handler/desktop
 */
jn.define('im/messenger/pull-handler/desktop', (require, exports, module) => {

	const { PullHandler } = jn.require('im/messenger/pull-handler/base');
	const { Logger } = jn.require('im/messenger/lib/logger');

	/**
	 * @class DesktopPullHandler
	 */
	class DesktopPullHandler extends PullHandler
	{
		handleDesktopOnline(params, extra, command)
		{
			Logger.info('DesktopPullHandler.handleDesktopOnline', params);

			BX.postComponentEvent('setDesktopStatus', [{
				isOnline: true,
				version: params.version,
			}], 'communication');
		}

		handleDesktopOffline(params, extra, command)
		{
			Logger.info('DesktopPullHandler.handleDesktopOffline', params);

			BX.postComponentEvent('setDesktopStatus', [{
				isOnline: false,
			}], 'communication');
		}
	}

	module.exports = {
		DesktopPullHandler,
	};
});
