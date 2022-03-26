(() => {
	/**
	 * @class Fields.TextArea
	 */
	class TextArea extends Fields.StringInput
	{
		constructor(props)
		{
			super(props);

			this.state = this.state || {};
			this.state.height = props.height || 20;
			this.state.isResizableByContent = props.isResizableByContent || !props.height;
		}

		getDefaultStyles()
		{
			const styles = super.getDefaultStyles();

			return {
				...styles,
				editableValue: {
					...styles.editableValue,
					height: this.state.height
				},
			};
		}

		renderEditableContent()
		{
			return TextInput({
				ref: ref => this.inputRef = ref,
				style: this.styles.editableValue,
				value: this.stringify(this.props.value),
				focus: this.state.focus,
				multiline: this.props.multiline || true,
				keyboardType: this.getConfig().keyboardType,
				placeholder: this.getPlaceholder(),
				placeholderTextColor: this.styles.textPlaceholder.color,
				onFocus: () => this.setFocus(),
				onBlur: () => this.removeFocus(),
				onChangeText: text => this.changeText(text),
				onContentSizeChange: data => this.changeSize(data)
			});
		}

		changeSize(data)
		{
			if (this.state.isResizableByContent)
			{
				this.setState({height: data.height});
			}
		}
	}

	this.Fields = this.Fields || {};
	this.Fields.TextArea = TextArea;
})();
