/**
 * @module layout/ui/copilot-role-selector/src/api
 */
jn.define('layout/ui/copilot-role-selector/src/api', (require, exports, module) => {
	const { RunActionExecutor } = require('rest/run-action-executor');

	const cacheId = 'layout/ui/copilot-role-selector/src/industries-list';
	const cacheTtl = 604_800; // 60 * 60 * 24 * 7;

	const loadIndustries = (skipRequestIfCacheExists = false) => {
		return new Promise((resolve) => {
			const handler = (response) => {
				if (response?.status === 'error')
				{
					console.error(response.errors);
				}
				resolve(response);
			};

			const executor = (new RunActionExecutor('mobile.ai.CopilotRoleManager.getIndustriesWithRoles'))
				.setHandler(handler)
				.setCacheHandler(handler)
				.setCacheId(cacheId)
				.setCacheTtl(cacheTtl);
			if (skipRequestIfCacheExists)
			{
				executor.setSkipRequestIfCacheExists();
			}
			executor.call(true);
		});
	};

	module.exports = { loadIndustries };
});
