/**
 * @module layout/ui/fields/project/theme/air-compact
 */
jn.define('layout/ui/fields/project/theme/air-compact', (require, exports, module) => {
	const { ProjectFieldClass } = require('layout/ui/fields/project');
	const { AirCompactThemeField } = require('layout/ui/fields/base/theme/air-compact');

	/** @type {function(object): object} */
	const ProjectField = AirCompactThemeField(ProjectFieldClass);

	module.exports = {
		ProjectField,
	};
});
