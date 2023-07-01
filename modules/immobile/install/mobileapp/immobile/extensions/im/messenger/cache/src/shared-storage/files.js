/* eslint-disable bitrix-rules/no-pseudo-private */
/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-bx-message */

/**
 * @module im/messenger/cache/files
 */
jn.define('im/messenger/cache/files', (require, exports, module) => {

	const { clone } = require('utils/object');
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
