/**
 * @module intranet/create-department/src/api
 */

jn.define('intranet/create-department/src/api', (require, exports, module) => {
	const { RunActionExecutor } = require('rest/run-action-executor');

	const cacheId = 'humanresources.api.Structure.Access.getDepartmentPermissions';
	const cacheTtl = 3600;

	const fetchDepartmentPermissions = async (handler = () => {}) => {
		return new RunActionExecutor(
			'humanresources.api.Structure.Access.getDepartmentPermissions',
			{},
		)
			.setCacheId(cacheId)
			.setCacheTtl(cacheTtl)
			.setCacheHandler(handler)
			.setHandler(handler)
			.call(true)
			.catch(console.error);
	};

	module.exports = {
		fetchDepartmentPermissions,
	};
});
