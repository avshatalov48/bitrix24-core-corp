(() => {
	class MultipleField extends Fields.BaseField
	{
		constructor(props)
		{
			super(props);
			/** @type {?number} */
			this.newInnerFieldIndex = null;
		}

		render()
		{
			this.styles = this.getStyles();

			const fields = this.props.value.map((value, index) => this.renderField(value, index));

			return View(
				{
					style: this.styles.wrapper
				},
				...fields
			);
		}

		renderField(value, index)
		{
			const field = this.props.renderField(
				{
					...this.props,
					value,
					multiple: false,
					title: this.getInnerFieldTitle(index),
					onChange: (value) => {
						this.newInnerFieldIndex = null;
						const val = [...this.props.value];
						val[index] = value;
						this.handleChange(val);
					},
					config: this.getInnerFieldConfig()
				}
			);

			if (this.newInnerFieldIndex === index)
			{
				field.focus();
			}

			return View(
				{
					style: this.styles.multipleFieldWrapper
				},
				View(
					{
						style: this.styles.multipleFieldContainer
					},
					field
				),
				this.renderAddOrDeleteFieldButton(index)
			);
		}

		getInnerFieldTitle (index)
		{
			if(index > 0)
			{
				const formatTitle = this.getFormatTitle(this.getConfig())
				if (formatTitle)
				{
					return formatTitle(index);
				}

				return `${this.props.title} ${index + 1}`;
			}

			return this.props.title;
		}

		getFormatTitle(config)
		{
			return BX.prop.getFunction(config, 'formatTitle', null);
		}

		renderAddOrDeleteFieldButton(index)
		{
			if (this.isReadOnly())
			{
				return null;
			}

			return View(
				{
					style: this.styles.addOrDeleteFieldButtonWrapper,
					onClick: () => {
						index === 0 ? this.onAddField() : this.onDeleteField(index)
					}
				},
				Image({
					style: this.styles.addOrDeleteFieldButtonContainer,
					resizeMode: 'center',
					svg: index === 0 ? svgImages.addField : svgImages.deleteField
				})
			);
		}

		onDeleteField(index)
		{
			let val = [...this.props.value];
			val.splice(index, 1);
			this.handleChange(val);
		}

		onAddField()
		{
			this.newInnerFieldIndex = this.props.value.length;
			this.handleChange([...this.props.value, null])
		}

		getInnerFieldConfig()
		{
			if (this.props.config)
			{
				return {
					...this.props.config,
					styles: this.getInnerFieldStyles()
				}
			}

			return this.getInnerFieldStyles();
		}

		getInnerFieldStyles()
		{
			return {
				wrapper: {
					paddingTop: 4,
					paddingBottom: 4
				},
				readOnlyWrapper: {
					paddingTop: 4,
					paddingBottom: 3
				}
			}
		}

		getDefaultStyles()
		{
			const styles = super.getDefaultStyles();

			return {
				...styles,
				multipleFieldWrapper: {
					flexDirection: 'row',
					flexWrap: 'no-wrap',
					alignItems: 'center',
					paddingTop: 2,
					paddingBottom: 2
				},
				multipleFieldContainer: {
					flexGrow: 2
				},
				wrapper: {
					flexDirection: 'column',
				},
				addOrDeleteFieldButtonWrapper: {
					width: 48,
					height: 48,
					justifyContent: 'center',
					alignItems: 'center',
					marginLeft: 7
				},
				addOrDeleteFieldButtonContainer: {
					width: 24,
					height: 24,
				}
			}
		}
	}

	const svgImages = {
		addField: {
			content: `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect opacity="0.3" x="0.25" y="0.25" width="23.5" height="23.5" rx="11.75" stroke="#767C87" stroke-width="0.5"/><path fill-rule="evenodd" clip-rule="evenodd" d="M13 6H11V11H6V13H11V18H13V13H18V11H13V6Z" fill="#A8ADB4"/></svg>`
		},
		deleteField: {
			content: `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect opacity="0.3" x="0.25" y="0.25" width="23.5" height="23.5" rx="11.75" stroke="#767C87" stroke-width="0.5"/><path fill-rule="evenodd" clip-rule="evenodd" d="M16.9497 8.46537L15.5355 7.05116L12 10.5867L8.46447 7.05116L7.05025 8.46537L10.5858 12.0009L7.05025 15.5364L8.46447 16.9507L12 13.4151L15.5355 16.9507L16.9497 15.5364L13.4142 12.0009L16.9497 8.46537Z" fill="#A8ADB4"/></svg>`
		}
	}

	this.Fields = this.Fields || {};
	this.Fields.MultipleField = MultipleField;
})();