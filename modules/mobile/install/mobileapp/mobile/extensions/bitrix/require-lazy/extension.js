(() => {
	const require = (ext) => jn.require(ext);
	const { NotifyManager } = require('notify-manager');

	/**
	 * Be careful! Prefix extension modules with a colon instead of a slash. (e.g. use crm:type, not crm/type)
	 *
	 * @function requireLazy
	 * @param {string} extensionNameWithColon
	 * @param {?boolean} showLoader
	 * @returns {Promise}
	 */
	async function requireLazy(extensionNameWithColon, showLoader = true)
	{
		if (showLoader)
		{
			NotifyManager.showLoadingIndicator();
		}

		try
		{
			await jn.import(extensionNameWithColon);
		}
		finally
		{
			if (showLoader)
			{
				NotifyManager.hideLoadingIndicatorWithoutFallback();
			}
		}

		const extensionWithoutNamespace = extensionNameWithColon.replace(':', '/');

		return require(extensionWithoutNamespace);
	}

	jnexport(requireLazy);
})();