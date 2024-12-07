/**
 * @module tasks/layout/fields/deadline/theme/air-compact
 */
jn.define('tasks/layout/fields/deadline/theme/air-compact', (require, exports, module) => {
	const { AirCompactThemeView } = require('layout/ui/fields/base/theme/air-compact');
	const { DeadlineField: DeadlineFieldClass } = require('tasks/layout/fields/deadline');
	const { withTheme } = require('layout/ui/fields/theme');

	const DeadlineField = withTheme(DeadlineFieldClass, ({ field }) => {
		const value = field.getValue();
		const count = (field.isEmpty() || !Array.isArray(value)) ? 0 : value.length;
		const deadline = value ? (value * 1000) : undefined;
		const now = Date.now();
		const isOverdue = deadline && deadline < now;

		return AirCompactThemeView({
			testId: field.testId,
			empty: field.isEmpty(),
			readOnly: field.isReadOnly(),
			hasError: isOverdue || field.hasErrorMessage(),
			multiple: field.isMultiple(),
			isRestricted: field.isRestricted(),
			leftIcon: field.getLeftIcon(),
			defaultLeftIcon: field.getDefaultLeftIcon(),
			text: field.isEmpty() ? field.getTitleText() : field.getDisplayedValue(),
			onClick: field.getContentClickHandler(),
			wideMode: Boolean(field.props.wideMode),
			colorScheme: field.props.colorScheme,
			count,
		});
	});

	module.exports = {
		DeadlineField,
	};
});
