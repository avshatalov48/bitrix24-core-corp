/**
 * @module layout/ui/fields/base/theme/air-compact
 */
jn.define('layout/ui/fields/base/theme/air-compact', (require, exports, module) => {
	const { withTheme } = require('layout/ui/fields/theme');
	const { AirCompactThemeView, ColorScheme } = require('layout/ui/fields/base/theme/air-compact/src/view');

	/**
	 * @param {object} field - instance of the FieldClassComponentClass.
	 * @return {object} - View
	 */
	const AirCompactThemeWrapper = ({ field }) => {
		const value = field.getValue();
		const count = (field.isEmpty() || !Array.isArray(value)) ? 0 : value.length;
		const { textMultiple = '' } = field.getConfig();

		return AirCompactThemeView({
			testId: field.testId,
			empty: field.isEmpty(),
			readOnly: field.isReadOnly(),
			hasError: field.hasErrorMessage(),
			multiple: field.isMultiple(),
			isRestricted: field.isRestricted(),
			leftIcon: field.getLeftIcon(),
			defaultLeftIcon: field.getDefaultLeftIcon(),
			text: field.isMultiple() ? field.getTitleText() : field.getDisplayedValue(),
			textMultiple,
			onClick: field.getContentClickHandler(),
			wideMode: Boolean(field.props.wideMode),
			colorScheme: field.props.colorScheme,
			showLoader: field.props.showLoader,
			count,
			bindContainerRef: field.bindContainerRef,
		});
	};

	/** @type {function(Class): function(object): object} */
	const AirCompactThemeField = (FieldComponentClass) => withTheme(FieldComponentClass, AirCompactThemeWrapper);

	module.exports = {
		AirCompactThemeWrapper,
		AirCompactThemeField,
		AirCompactThemeView,
		ColorScheme,
	};
});
