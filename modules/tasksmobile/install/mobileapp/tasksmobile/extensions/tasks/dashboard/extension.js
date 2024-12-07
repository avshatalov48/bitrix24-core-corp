/**
 * @module tasks/dashboard
 */
jn.define('tasks/dashboard', (require, exports, module) => {
	const { SettingsActionExecutor } = require('tasks/dashboard/settings-action-executor');
	const { NavigationTitle } = require('tasks/dashboard/src/navigation-title');
	const { Pull } = require('tasks/dashboard/src/pull');
	const { TasksDashboardFilter } = require('tasks/dashboard/filter');
	const { TasksDashboardMoreMenu } = require('tasks/dashboard/src/more-menu');
	const { TasksDashboardSorting } = require('tasks/dashboard/src/sorting');

	module.exports = {
		SettingsActionExecutor,
		NavigationTitle,
		Pull,
		TasksDashboardFilter,
		TasksDashboardMoreMenu,
		TasksDashboardSorting,
	};
});
