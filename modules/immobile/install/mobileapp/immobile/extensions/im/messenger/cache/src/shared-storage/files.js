/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/cache/files
 */
jn.define('im/messenger/cache/files', (require, exports, module) => {
	const { Cache } = require('im/messenger/cache/base');

	/**
	 * @class FilesCache
	 */
	class FilesCache extends Cache
	{
		constructor()
		{
			super({
				name: 'files',
			});
		}

		save()
		{
			return Promise.resolve();
		}
	}

	module.exports = {
		FilesCache: new FilesCache(),
	};
});
