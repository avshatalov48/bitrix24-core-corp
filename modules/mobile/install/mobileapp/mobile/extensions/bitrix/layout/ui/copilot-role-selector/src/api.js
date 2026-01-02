/**
 * @module layout/ui/copilot-role-selector/src/api
 */
jn.define('layout/ui/copilot-role-selector/src/api', (require, exports, module) => {
	const { RunActionExecutor } = require('rest/run-action-executor');

	const getAnalyticsParams = () => {
		return {
			bx_module: 'mobile',
			bx_context: 'chat',
		};
	};

	const loadRoles = () => {
		return new Promise((resolve) => {
			const handler = (response) => {
				if (response?.status === 'error')
				{
					console.error(response.errors);
				}
				resolve(response);
			};

			const executor = (new RunActionExecutor('ai.api.role.picker', {
				parameters: getAnalyticsParams(),
			}))
				.setHandler(handler);
			executor.call();
		});
	};

	const addRoleToFavorite = (roleCode) => {
		return new Promise((resolve) => {
			const handler = (response) => {
				if (response?.status === 'error')
				{
					console.error(response.errors);
				}
				resolve(response);
			};

			const executor = (new RunActionExecutor('ai.api.role.addFavorite', {
				parameters: getAnalyticsParams(),
				roleCode,
			}))
				.setHandler(handler);
			executor.call(true);
		});
	};

	const removeRoleFromFavorite = (roleCode) => {
		return new Promise((resolve) => {
			const handler = (response) => {
				if (response?.status === 'error')
				{
					console.error(response.errors);
				}
				resolve(response);
			};

			const executor = (new RunActionExecutor('ai.api.role.removeFavorite', {
				parameters: getAnalyticsParams(),
				roleCode,
			}))
				.setHandler(handler);
			executor.call(true);
		});
	};

	module.exports = { loadRoles, addRoleToFavorite, removeRoleFromFavorite };
});
