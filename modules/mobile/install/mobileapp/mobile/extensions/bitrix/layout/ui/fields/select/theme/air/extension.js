/**
 * @module layout/ui/fields/select/theme/air
 */
jn.define('layout/ui/fields/select/theme/air', (require, exports, module) => {
	const { withTheme } = require('layout/ui/fields/theme');
	const { SelectFieldClass } = require('layout/ui/fields/select');
	const { InputSize, InputDesign, InputMode, Input } = require('ui-system/form/inputs/input');

	/**
	 * @param {SelectField} field
	 */
	const AirTheme = ({ field }) => View(
		{
			testId: `${field.testId}_FIELD`,
			ref: field.bindContainerRef,
			onClick: field.getContentClickHandler(),
			onLongClick: field.getContentLongClickHandler(),
		},
		Input({
			testId: `${field.testId}_DROPDOWN`,
			readOnly: true,
			dropdown: !field.isReadOnly() && !field.isRestricted(),
			locked: field.isRestricted(),
			required: field.isRequired(),
			value: (field.isEmpty() ? field.getEmptyText() : field.getSelectedItemsText()),
			label: (field.shouldShowTitle() ? field.getTitleText() : ''),
			size: InputSize.L,
			design: InputDesign.GREY,
			mode: InputMode.STROKE,
			onClick: field.getContentClickHandler(),
			onLongClick: field.getContentLongClickHandler(),
		}),
	);

	/**
	 * @type {function(Object): Object}
	 */
	const SelectField = withTheme(SelectFieldClass, AirTheme);

	module.exports = {
		AirTheme,
		SelectField,
	};
});
