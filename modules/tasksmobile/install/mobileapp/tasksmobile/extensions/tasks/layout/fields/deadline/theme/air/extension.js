/**
 * @module tasks/layout/fields/deadline/theme/air
 */
jn.define('tasks/layout/fields/deadline/theme/air', (require, exports, module) => {
	const { withTheme } = require('layout/ui/fields/theme');
	const { AirTheme } = require('layout/ui/fields/datetime/theme/air');
	const { DeadlineField: DeadlineFieldClass } = require('tasks/layout/fields/deadline');

	/** @type {function(object): object} */
	const DeadlineField = withTheme(DeadlineFieldClass, AirTheme);

	module.exports = {
		DeadlineField,
	};
});
