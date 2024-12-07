/**
 * @module layout/ui/fields/file/theme/air-compact
 */
jn.define('layout/ui/fields/file/theme/air-compact', (require, exports, module) => {
	const { FileFieldClass } = require('layout/ui/fields/file');
	const { AirCompactThemeView } = require('layout/ui/fields/base/theme/air-compact');
	const { withTheme } = require('layout/ui/fields/theme');

	/**
	 * @param {FileField} field - instance of the FileField.
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
			hasError: field.hasFilesWithErrors(),
			multiple: field.isMultiple(),
			isRestricted: field.isRestricted(),
			leftIcon: field.getLeftIcon(),
			defaultLeftIcon: field.getDefaultLeftIcon(),
			text: field.getTitleText(),
			textMultiple,
			onClick: field.getContentClickHandler(),
			count,
			showLoader: field.hasUploadingFiles(true),
		});
	};

	/** @type {function(Class): function(object): object} */
	const AirCompactThemeField = (FieldComponentClass) => withTheme(FieldComponentClass, AirCompactThemeWrapper);

	/** @type {function(object): object} */
	const FileField = AirCompactThemeField(FileFieldClass);

	module.exports = {
		AirCompactThemeField,
		FileField,
	};
});
