/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/provider/pull/base
 */
jn.define('im/messenger/provider/pull/base', (require, exports, module) => {

	const { core } = require('im/messenger/core');

	/**
	 * @class PullHandler
	 */
	class PullHandler
	{
		constructor(options = {})
		{
			this.store = core.getStore();
		}

		getModuleId()
		{
			return 'im';
		}
	}

	module.exports = {
		PullHandler,
	};
});
