/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/provider/pull/chat/desktop
 */
jn.define('im/messenger/provider/pull/chat/desktop', (require, exports, module) => {
	const { BasePullHandler } = require('im/messenger/provider/pull/base');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('pull-handler--desktop');

	/**
	 * @class DesktopPullHandler
	 */
	class DesktopPullHandler extends BasePullHandler
	{
		handleDesktopOnline(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			logger.info('DesktopPullHandler.handleDesktopOnline', params);

			BX.postComponentEvent('setDesktopStatus', [{
				isOnline: true,
				version: params.version,
			}], 'communication');
		}

		handleDesktopOffline(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			logger.info('DesktopPullHandler.handleDesktopOffline', params);

			BX.postComponentEvent('setDesktopStatus', [{
				isOnline: false,
			}], 'communication');
		}
	}

	module.exports = {
		DesktopPullHandler,
	};
});
