/**
 * @module layout/ui/fields/base/theme/air-compact
 */
jn.define('layout/ui/fields/base/theme/air-compact', (require, exports, module) => {
	const { withTheme } = require('layout/ui/fields/theme');
	const { AirCompactThemeView } = require('layout/ui/fields/base/theme/air-compact/src/view');

	/**
	 * @param {object} field - instance of the FieldClassComponentClass.
	 * @return {object} - View
	 */
	const AirCompactThemeWrapper = ({ field }) => {
		const value = field.getValue();
		const count = (field.isEmpty() || !Array.isArray(value)) ? 0 : value.length;
		const onClick = field.focus.bind(field);

		return AirCompactThemeView({
			testId: field.testId,
			empty: field.isEmpty(),
			multiple: field.isMultiple(),
			leftIcon: field.getLeftIcon(),
			text: field.isEmpty() ? field.getTitleText() : field.getDisplayedValue(),
			onClick,
			count,
		});
	};

	/** @type {function(Class): function(object): object} */
	const AirCompactThemeField = (FieldComponentClass) => withTheme(FieldComponentClass, AirCompactThemeWrapper);

	module.exports = {
		AirCompactThemeField,
		AirCompactThemeView,
	};
});
