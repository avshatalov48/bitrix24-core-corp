(() => {
	/**
	 * @class Fields.StringInput
	 */
	class StringInput extends Fields.BaseField
	{
		constructor(props)
		{
			super(props);
			this.inputRef = null;
		}

		getConfig()
		{
			const config = super.getConfig();

			return {
				...config,
				keyboardType: 'default',
				selectionOnFocus: BX.prop.getBoolean(config, 'selectionOnFocus', false)
			};
		}

		hasKeyboard()
		{
			return true;
		}

		isEmptyValue(value)
		{
			return this.stringify(value).trim() === '';
		}

		getDefaultStyles()
		{
			const styles = super.getDefaultStyles();

			return {
				...styles,
				editableValue: {
					...styles.base,
					color: '#333333'
				},
				textPlaceholder: {
					color: '#A8ADB4'
				}
			};
		}

		renderReadOnlyContent()
		{
			if (this.isEmpty())
			{
				return this.renderEmptyContent();
			}

			return Text(
				{
					style: this.styles.value,
					text: this.stringify(this.props.value)
				}
			);
		}

		renderEditableContent()
		{
			return TextField({
				ref: (ref) => this.inputRef = ref,
				style: this.styles.editableValue,
				forcedValue: this.stringify(this.props.value),
				focus: this.state.focus,
				keyboardType: this.getConfig().keyboardType,
				placeholder: this.getPlaceholder(),
				placeholderTextColor: this.styles.textPlaceholder.color,
				onFocus: () => this.setFocus(),
				onBlur: () => this.removeFocus(),
				onChangeText: (text) => this.changeText(text),
				onSubmitEditing: () => this.inputRef.blur()
			});
		}

		getPlaceholder()
		{
			return this.props.placeholder || BX.message('FIELDS_INLINE_FIELD_EMPTY_STRING_PLACEHOLDER');
		}

		changeText(currentText)
		{
			this.handleChange(currentText);
		}

		setFocus(callback = null)
		{
			if (this.getConfig().selectionOnFocus)
			{
				const val = this.stringify(this.props.value);
				this.inputRef.setSelection(0, val.length);
			}

			super.setFocus(callback);
		}

		focus()
		{
			super.focus();

			if (this.inputRef)
			{
				this.inputRef.focus();
			}
		}

		/**
		 * @param {any} value
		 * @returns {String}
		 */
		stringify(value)
		{
			if (typeof value === 'undefined' || value === null)
			{
				return '';
			}
			return String(value);
		}
	}

	this.Fields = this.Fields || {};
	this.Fields.StringInput = StringInput;
})();
