/**
 * @module layout/ui/fields/project/theme/air
 */
jn.define('layout/ui/fields/project/theme/air', (require, exports, module) => {
	const { ProjectFieldClass } = require('layout/ui/fields/project');
	const { withTheme } = require('layout/ui/fields/theme');
	const { AirTheme } = require('layout/ui/fields/entity-selector/theme/air');

	/** @type {function(object): object} */
	const ProjectField = withTheme(ProjectFieldClass, AirTheme);

	module.exports = {
		ProjectField,
	};
});
