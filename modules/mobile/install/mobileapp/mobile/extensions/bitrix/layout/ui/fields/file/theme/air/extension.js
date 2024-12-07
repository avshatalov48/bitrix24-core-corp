/**
 * @module layout/ui/fields/file/theme/air
 */
jn.define('layout/ui/fields/file/theme/air', (require, exports, module) => {
	const { FileFieldClass } = require('layout/ui/fields/file');
	const { withTheme } = require('layout/ui/fields/theme');
	const { FieldWrapper } = require('layout/ui/fields/theme/air/elements/field-wrapper');
	const { EmptyContent } = require('layout/ui/fields/theme/air/elements/empty-content');
	const { Content } = require('layout/ui/fields/file/theme/air/src/content');
	const { Indent } = require('tokens');

	const AirTheme = ({ field }) => FieldWrapper(
		{
			field,
			titleIndent: Indent.XS,
		},
		field.isEmpty()
			? EmptyContent({
				icon: field.getDefaultLeftIcon(),
				text: field.getTitleText(),
				testId: field.testId,
			})
			: Content({ field }),
	);

	const FileField = withTheme(FileFieldClass, AirTheme);

	module.exports = {
		AirTheme,
		FileField,
	};
});
