/**
 * @module im/messenger/assets
 */
jn.define('im/messenger/assets', (require, exports, module) => {
	const AppTheme = require('apptheme');

	const ASSETS_ROOT = `${currentDomain}/bitrix/mobileapp/immobile/extensions/im/messenger/assets`;

	/**
	 * @public
	 * @param {string} filename
	 * @param {string} [folder]
	 * @return {string}
	 */
	const makeLibraryImagePath = (filename, folder) => {
		if (folder)
		{
			return `${ASSETS_ROOT}/${folder}/${AppTheme.id}/${filename}`;
		}

		return `${ASSETS_ROOT}/${AppTheme.id}/${filename}`;
	};

	module.exports = { makeLibraryImagePath };
});
