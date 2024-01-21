/**
 * @module im/messenger/provider/pull/file
 */
jn.define('im/messenger/provider/pull/file', (require, exports, module) => {
	const { PullHandler } = require('im/messenger/provider/pull/base');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('pull-handler--file');

	/**
	 * @class FilePullHandler
	 */
	class FilePullHandler extends PullHandler
	{
		handleFileAdd(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			logger.info('FilePullHandler.handleFileAdd: ', params);

			this.store.dispatch('filesModel/set', params.files);
		}
	}

	module.exports = {
		FilePullHandler,
	};
});
