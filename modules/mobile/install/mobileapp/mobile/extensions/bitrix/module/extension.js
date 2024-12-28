/**
 * @module module
 */
jn.define('module', (require, exports, module) => {
	const { Type } = require('type');

	/**
	 * @param {string} moduleId
	 * @return {boolean}
	 */
	function isModuleInstalled(moduleId)
	{
		if (!Type.isStringFilled(moduleId))
		{
			return false;
		}

		const alwaysInstalledModules = {
			mobile: true,
			mobileapp: true,
		};
		if (alwaysInstalledModules[moduleId])
		{
			return true;
		}

		const installedModules = env.installedModules || {};
		const isInstalled = Boolean(installedModules[moduleId]);
		const isMobileInstalled = Boolean(installedModules[`${moduleId}mobile`]);

		return isInstalled && isMobileInstalled;
	}

	module.exports = {
		isModuleInstalled,
	};
});
