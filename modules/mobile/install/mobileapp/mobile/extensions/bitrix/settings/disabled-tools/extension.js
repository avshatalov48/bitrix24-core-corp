/**
 * @module settings/disabled-tools
 */
jn.define('settings/disabled-tools', (require, exports, module) => {
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { Type } = require('type');
	/**
	 * @public
	 * @param {string} toolId
	 * @returns {Promise<boolean>}
	 */
	async function checkDisabledToolById(toolId)
	{
		const disabledTools = await getDisabledTools();

		console.log(disabledTools);
		if (!Type.isNil(disabledTools))
		{
			return toolId in disabledTools;
		}

		return false;
	}

	async function getDisabledTools()
	{
		let response = getCachedDisabledTools();

		if (!response?.data)
		{
			response = await fetchDisabledTools();
		}

		if (response?.errors?.length > 0)
		{
			console.error(response.errors);
		}

		return response.data;
	}

	function getCachedDisabledTools()
	{
		const executor = getDisabledToolsActionExecutor();

		return executor.getCache().getData();
	}

	async function fetchDisabledTools()
	{
		const executor = getDisabledToolsActionExecutor();
		const response = await executor.call(true);
		if (response?.errors?.length > 0)
		{
			console.error(response.errors);
		}

		return response;
	}

	function getDisabledToolsActionExecutor()
	{
		const executor = new RunActionExecutor('mobile.disabledtools.getDisabledMenuItemListId');
		executor.setCacheId('disabledTools');
		executor.setCacheTtl(3600);

		return executor;
	}

	module.exports = { checkDisabledToolById };
});
