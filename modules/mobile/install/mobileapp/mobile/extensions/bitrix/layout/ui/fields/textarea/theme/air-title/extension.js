/**
 * @module layout/ui/fields/textarea/theme/air-title
 */
jn.define('layout/ui/fields/textarea/theme/air-title', (require, exports, module) => {
	const { Color, Typography } = require('tokens');
	const { withTheme } = require('layout/ui/fields/theme');
	const { TextAreaFieldClass } = require('layout/ui/fields/textarea');
	const { CollapsibleText } = require('layout/ui/collapsible-text');

	const titleStyle = {
		...Typography.h3.getStyle(),
		color: Color.base1.toHex(),
	};

	/**
	 * @param {TextAreaField} field
	 */
	const AirTheme = ({ field }) => View(
		{
			testId: `${field.testId}_FIELD`,
			ref: field.bindContainerRef,
			onClick: field.getContentClickHandler(),
		},
		field.isReadOnly()
			? View(
				{},
				field.isEmpty()
					? Text({
						testId: `${field.testId}_CONTENT`,
						style: titleStyle,
						text: field.getEmptyText(),
					})
					: new CollapsibleText({
						testId: `${field.testId}_CONTENT`,
						value: field.getValue(),
						onLinkClick: field.getOnLinkClick(),
						onLongClick: field.getContentLongClickHandler(),
						style: titleStyle,
						maxLettersCount: 300,
						maxNewLineCount: 10,
					}),
			)
			: TextInput({
				testId: `${field.testId}_CONTENT`,
				ref: field.bindInputRef,
				style: titleStyle,
				value: field.getValue(),
				forcedValue: field.getForcedValue(),
				focus: field.state.focus || undefined,
				keyboardType: field.getConfig().keyboardType,
				autoCapitalize: field.getConfig().autoCapitalize,
				enableKeyboardHide: field.getConfig().enableKeyboardHide,
				placeholder: field.getPlaceholder(),
				placeholderTextColor: Color.base3.toHex(),
				onFocus: field.setFocus,
				onBlur: field.onBlur,
				onChangeText: field.debouncedChangeText,
				onLinkClick: field.getOnLinkClick(),

				multiline: field.isMultiline(),
				// enable: !(Application.getPlatform() === 'ios' && !field.isFocused()),
				onSubmitEditing: () => Keyboard.dismiss(),
			}),
	);

	/**
	 * @type {function(Object): Object}
	 */
	const TextAreaField = withTheme(TextAreaFieldClass, AirTheme);

	module.exports = {
		AirTheme,
		TextAreaField,
	};
});
