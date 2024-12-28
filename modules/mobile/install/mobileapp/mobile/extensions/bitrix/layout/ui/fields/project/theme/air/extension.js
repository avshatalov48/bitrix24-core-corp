/**
 * @module layout/ui/fields/project/theme/air
 */
jn.define('layout/ui/fields/project/theme/air', (require, exports, module) => {
	const { withTheme } = require('layout/ui/fields/theme');
	const { ProjectFieldClass } = require('layout/ui/fields/project');
	const { Content } = require('layout/ui/fields/entity-selector/theme/air');
	const { FieldWrapper } = require('layout/ui/fields/theme/air/elements/field-wrapper');
	const { ProjectAirThemeEntity } = require('layout/ui/fields/project/theme/air/src/entity');

	const AirTheme = ({ field }) => FieldWrapper(
		{ field },
		Content((props) => new ProjectAirThemeEntity(props))({ field }),
	);

	/** @type {function(object): object} */
	const ProjectField = withTheme(ProjectFieldClass, AirTheme);

	module.exports = {
		ProjectField,
	};
});
