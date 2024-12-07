/**
 * @module tasks/layout/fields/task/theme/air
 */
jn.define('tasks/layout/fields/task/theme/air', (require, exports, module) => {
	const { withTheme } = require('layout/ui/fields/theme');
	const { FieldWrapper } = require('layout/ui/fields/theme/air/elements/field-wrapper');
	const { Content } = require('layout/ui/fields/entity-selector/theme/air');

	const { Entity } = require('tasks/layout/fields/task/theme/air/src/entity');
	const { TaskFieldClass } = require('tasks/layout/task/fields/task');

	/**
	 * @param  {TaskField} field - instance of the SubTasks.
	 */
	const AirTheme = ({ field }) => FieldWrapper(
		{ field },
		Content(Entity)({ field }),
	);

	/** @type {function(object): object} */
	const TaskField = withTheme(TaskFieldClass, AirTheme);

	module.exports = {
		AirTheme,
		TaskField,
	};
});
