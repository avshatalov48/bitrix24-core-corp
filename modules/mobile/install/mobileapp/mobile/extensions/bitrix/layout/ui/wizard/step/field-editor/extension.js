(() =>
{
	const styles = {
		editor: {
			container: {
				backgroundColor: '#ffffff',
				borderRadius: 15,
				paddingTop: 8,
				paddingBottom: 8,
				paddingLeft: 18,
				paddingRight: 18,
			},
		}
	};

	class FieldsLayout extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.state = {
				fieldValues: props.fields.reduce(
					(values, field) => (
						field.type === FieldFactory.Type.COMBINED
							? {
								...values,
								[field.primaryField.id]: field.primaryField.value,
								[field.secondaryField.id]: field.secondaryField.value
							}
							: {
							...values,
								[field.id]: field.value
							}
					),
					{}
				) // convert [{id1,value1}, {id2,value2}] to {id1: value1, id2: value2}
			};
			this.focusOnFirstField = props.focusOnFirstField;
			this.fieldRefMap = {};
		}

		setFieldValue(fieldId, fieldValue)
		{
			const fieldValues = this.state.fieldValues;
			fieldValues[fieldId] = fieldValue;

			this.setState({fieldValues});
		}

		render()
		{
			return View(
				{},
				this.props.renderHeader(),
				this.renderEditor(),
				this.props.renderFooter(),
			);
		}

		renderEditor()
		{
			/** @type {Fields.BaseField[]} */
			const fields = this.props.fields.map((field) =>
				(field.type === FieldFactory.Type.COMBINED)
					? FieldFactory.create(
						field.type,
						{
							primaryField: FieldFactory.create(field.primaryField.type, {
								...field.primaryField,
								readOnly: false,
								value: this.getFieldValue(field.primaryField.id),
								onChange: this.onChangeValue.bind(this, field.primaryField.id),
							}),
							secondaryField: FieldFactory.create(field.secondaryField.type, {
								...field.secondaryField,
								readOnly: false,
								value: this.getFieldValue(field.secondaryField.id),
								onChange: this.onChangeValue.bind(this, field.secondaryField.id),
							}),
							ref: (ref) => this.processFieldRef(field.id, ref),
						}
					)
					: FieldFactory.create(
						field.type,
						{
							...field,
							value: this.getFieldValue(field.id),
							readOnly: false,
							onChange: this.onChangeValue.bind(this, field.id),
							ref: (ref) => this.processFieldRef(field.id, ref),
						}
					)
			);

			return FieldsWrapper({
				fields,
				config: {
					styles: styles.editor.container,
				},
			});
		}

		processFieldRef(fieldId, ref)
		{
			this.fieldRefMap[fieldId] = ref;
			if (this.props.fields.length && this.props.fields[0].id === fieldId && this.focusOnFirstField)
			{
				this.focusOnFirstField = false;
				setTimeout(
					() => this.fieldRefMap[fieldId].focus(),
					BX.prop.getNumber(this.props, 'fieldFocusDelay', 100)
				);
			}
		}

		getFieldValue(fieldId)
		{
			let value = null;
			const field = this.props.fields.find(item => item.id === fieldId);

			if (this.state.fieldValues.hasOwnProperty(fieldId))
			{
				value = this.state.fieldValues[fieldId];
			}
			else if (field && typeof field.value != 'undefined')
			{
				value = field.value;
			}

			return value;
		}

		validate()
		{
			let validationResult = true;
			Object.values(this.fieldRefMap).forEach(field => (validationResult &= field.validate()));

			return validationResult;
		}

		onChangeValue(fieldId, fieldValue, options)
		{
			this.setFieldValue(fieldId, fieldValue);
			if (this.props.onChange)
			{
				this.props.onChange(fieldId, fieldValue, options);
			}
		}
	}

	class FieldEditorStep extends WizardStep
	{
		constructor()
		{
			super();
			this.fields = [];
			this.editorRef = null;
		}

		clearFields()
		{
			this.fields = [];
		}

		addField(id, type, title, value, extraProps = {})
		{
			this.fields.push({
				id,
				type,
				title,
				value,
				...extraProps
			});
		}

		addCombinedField(primaryField, secondaryField)
		{
			this.fields.push({
				type: FieldFactory.Type.COMBINED,
				primaryField,
				secondaryField,
			});
		}

		prepareFields()
		{
		}

		createLayout(props)
		{
			this.prepareFields();

			return View(
				{},
				new FieldsLayout({
					renderHeader: () => this.renderHeader(),
					renderFooter: () => this.renderFooter(),
					fields: this.fields,
					onChange: this.onChange.bind(this),
					focusOnFirstField: true,
					...props,
					ref: (ref) => this.editorRef = ref,
				})
			);
		}

		renderHeader()
		{
			return null;
		}

		renderFooter()
		{
			return null;
		}

		onChange(fieldId, fieldValue, options)
		{
			const field = this.fields.find(item => item.id === fieldId);
			if (field)
			{
				field.value = fieldValue;
			}
		}

		onMoveToNextStep()
		{
			return (this.editorRef && this.editorRef.validate())
				? Promise.resolve()
				: Promise.reject()
			;
		}
	}

	this.FieldEditorStep = FieldEditorStep;
})();
