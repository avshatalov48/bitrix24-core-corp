/**
 * @module intranet/user-list
 */
jn.define('intranet/user-list', (require, exports, module) => {
	const { UserListFilter } = require('intranet/user-list/src/filter');
	const { UserListMoreMenu } = require('intranet/user-list/src/more-menu');
	const { UserListSorting } = require('intranet/user-list/src/sorting');
	const { DepartmentButton } = require('intranet/user-list/src/department-button');

	module.exports = {
		UserListFilter,
		UserListMoreMenu,
		UserListSorting,
		DepartmentButton,
	};
});
