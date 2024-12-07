/**
 * @module layout/ui/fields/file-with-background-attach/theme/air
 */
jn.define('layout/ui/fields/file-with-background-attach/theme/air', (require, exports, module) => {
	const { withTheme } = require('layout/ui/fields/theme');
	const { AirTheme } = require('layout/ui/fields/file/theme/air');
	const { FileWithBackgroundAttachFieldClass } = require('layout/ui/fields/file-with-background-attach');

	const FileWithBackgroundAttachField = withTheme(FileWithBackgroundAttachFieldClass, AirTheme);

	module.exports = {
		AirTheme,
		FileWithBackgroundAttachField,
	};
});
