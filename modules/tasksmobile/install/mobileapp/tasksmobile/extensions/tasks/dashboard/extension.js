/**
 * @module tasks/dashboard
 */
jn.define('tasks/dashboard', (require, exports, module) => {
	const { Filter } = require('tasks/dashboard/src/filter');
	const { MoreMenu } = require('tasks/dashboard/src/more-menu');
	const { NavigationTitle } = require('tasks/dashboard/src/navigation-title');
	const { Pull } = require('tasks/dashboard/src/pull');
	const { Sorting } = require('tasks/dashboard/src/sorting');

	module.exports = {
		Filter,
		MoreMenu,
		NavigationTitle,
		Pull,
		Sorting,
	};
});
