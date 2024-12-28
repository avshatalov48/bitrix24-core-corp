/**
 * @module selector/widget/entity/nested-department-selector
 */
jn.define('selector/widget/entity/nested-department-selector', (require, exports, module) => {
	const { NestedDepartmentSelector } = require('selector/widget/entity/tree-selectors/nested-department-selector');
	const { Navigator } = require('selector/widget/entity/tree-selectors/shared/navigator');

	module.exports = { NestedDepartmentSelector, findInTree: Navigator.findInTree };
});
