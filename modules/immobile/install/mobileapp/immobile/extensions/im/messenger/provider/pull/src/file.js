/**
 * @module im/messenger/provider/pull/file
 */
jn.define('im/messenger/provider/pull/file', (require, exports, module) => {
	const { PullHandler } = require('im/messenger/provider/pull/base');
	const { Logger } = require('im/messenger/lib/logger');

	/**
	 * @class FilePullHandler
	 */
	class FilePullHandler extends PullHandler
	{
		handleFileAdd(params)
		{
			Logger.info('FilePullHandler.handleFileAdd: ', params);

			this.store.dispatch('filesModel/set', params.files);
		}
	}

	module.exports = {
		FilePullHandler,
	};
});
