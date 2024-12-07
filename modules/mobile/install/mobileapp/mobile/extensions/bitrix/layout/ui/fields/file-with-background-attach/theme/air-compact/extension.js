/**
 * @module layout/ui/fields/file-with-background-attach/theme/air-compact
 */
jn.define('layout/ui/fields/file-with-background-attach/theme/air-compact', (require, exports, module) => {
	const { AirCompactThemeField } = require('layout/ui/fields/file/theme/air-compact');
	const { FileWithBackgroundAttachFieldClass } = require('layout/ui/fields/file-with-background-attach');

	const FileWithBackgroundAttachField = AirCompactThemeField(FileWithBackgroundAttachFieldClass);

	module.exports = {
		AirCompactThemeField,
		FileWithBackgroundAttachField,
	};
});
