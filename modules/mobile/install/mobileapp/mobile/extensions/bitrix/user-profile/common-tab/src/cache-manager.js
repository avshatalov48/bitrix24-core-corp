/**
 * @module user-profile/common-tab/src/cache-manager
 */
jn.define('user-profile/common-tab/src/cache-manager', (require, exports, module) => {
	/**
	 * @class TabsCacheManager
	 */
	class TabsCacheManager
	{
		constructor()
		{
			/** @type {RunActionExecutor|null} */
			this.runActionExecutor = null;
		}

		/**
		 * @public
		 */
		getCache()
		{
			if (!this.isEnabled())
			{
				return null;
			}

			return this.runActionExecutor.getCache();
		}

		getData()
		{
			if (!this.isEnabled())
			{
				return null;
			}

			return this.getCache().getData();
		}

		saveData(modifiedData)
		{
			if (!this.isEnabled())
			{
				return;
			}

			this.getCache().saveDataDebounced(modifiedData);
		}

		/**
		 * @public
		 * @param {RunActionExecutor} runActionExecutor
		 */
		setRunActionExecutor(runActionExecutor)
		{
			this.runActionExecutor = runActionExecutor;
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		isEnabled()
		{
			return Boolean(this.runActionExecutor);
		}

		modifyCommonTabDataInCache(modifiedData)
		{
			if (!this.isEnabled())
			{
				return;
			}

			const currentData = this.getData();
			const currentTabs = currentData?.data?.tabs ?? [];
			const commonTabIndex = currentTabs.findIndex((tab) => tab.id === 'common');
			if (currentData && commonTabIndex !== -1)
			{
				currentTabs[commonTabIndex].params.data = modifiedData ?? {};

				const newData = currentData;
				newData.data.tabs = currentTabs;
				this.saveData(newData);
			}
		}
	}

	module.exports = { TabsCacheManager };
});
