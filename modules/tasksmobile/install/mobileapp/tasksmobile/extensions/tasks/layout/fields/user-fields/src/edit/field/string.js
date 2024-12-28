/**
 * @module tasks/layout/fields/user-fields/edit/field/string
 */
jn.define('tasks/layout/fields/user-fields/edit/field/string', (require, exports, module) => {
	const { EditBaseField } = require('tasks/layout/fields/user-fields/edit/field/base');
	const { getBaseInputFieldProps } = require('tasks/layout/fields/user-fields/edit/field/input');
	const { TextEditor } = require('text-editor');
	const { TextAreaInput } = require('ui-system/form/inputs/textarea');
	const { Icon } = require('ui-system/blocks/icon');
	const { useCallback } = require('utils/function');
	const { Loc } = require('tasks/loc');

	class EditStringField extends EditBaseField
	{
		renderSingleValue(value, index = 0)
		{
			return TextAreaInput({
				...getBaseInputFieldProps(value, index, this),
				readOnly: true,
				showCharacterCount: this.shouldShowCharactersCount,
				placeholder: (
					this.isReadOnly ? '' : Loc.getMessage('TASKS_FIELDS_USER_FIELDS_EDIT_STRING_PLACEHOLDER')
				),
				onClick: useCallback(() => {
					void TextEditor.edit({
						value,
						allowBBCode: false,
						title: this.props.title,
						readOnly: this.isReadOnly,
						parentWidget: this.parentWidget,
						autoFocus: true,
						closeOnSave: true,
						textInput: {
							placeholder: Loc.getMessage('TASKS_FIELDS_USER_FIELDS_EDIT_STRING_EDITOR_PLACEHOLDER'),
						},
						onSave: useCallback(({ bbcode }) => this.updateValue(bbcode, index)),
					});
				}),
			});
		}

		focus(ref)
		{
			ref?.handleOnClick?.();
		}

		get icon()
		{
			return Icon.TEXT;
		}

		get shouldShowCharactersCount()
		{
			return this.shouldShowSettingsInfoHint;
		}

		get shouldShowSettingsInfoHint()
		{
			const { minLength = 0, maxLength = 0 } = this.settings;

			return minLength !== 0 || maxLength !== 0;
		}

		getSettingsInfoDescription()
		{
			const { minLength = 0, maxLength = 0 } = this.settings;
			const minLengthDescription = Loc.getMessagePlural(
				'TASKS_FIELDS_USER_FIELDS_EDIT_SETTINGS_INFO_MIN_LENGTH',
				minLength,
				{
					'#VALUE#': minLength,
				},
			);
			const maxLengthDescription = Loc.getMessagePlural(
				'TASKS_FIELDS_USER_FIELDS_EDIT_SETTINGS_INFO_MAX_LENGTH',
				maxLength,
				{
					'#VALUE#': maxLength,
				},
			);

			return (
				(minLength ? `\n${minLengthDescription}` : '')
				+ (maxLength ? `\n${maxLengthDescription}` : '')
			);
		}
	}

	module.exports = { EditStringField };
});
