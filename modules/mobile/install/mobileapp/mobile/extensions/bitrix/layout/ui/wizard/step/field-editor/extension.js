/**
 * @module layout/ui/wizard/step/field-editor
 */
jn.define('layout/ui/wizard/step/field-editor', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { FieldFactory, CombinedType } = require('layout/ui/fields');
	const { WizardStep } = require('layout/ui/wizard/step');

	const styles = {
		editor: {
			container: {
				backgroundColor: AppTheme.colors.bgContentPrimary,
				borderRadius: 12,
				paddingTop: 8,
				paddingBottom: 8,
				paddingLeft: 18,
				paddingRight: 18,
			},
		},
	};

	/**
	 * @class FieldsLayout
	 */
	class FieldsLayout extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.state = {
				fieldValues: props.fields.reduce(
					(values, field) => (
						field.type === CombinedType
							? {
								...values,
								[field.primaryField.id]: field.primaryField.value,
								[field.secondaryField.id]: field.secondaryField.value,
							}
							: {
								...values,
								[field.id]: field.value,
							}
					),
					{},
				), // convert [{id1,value1}, {id2,value2}] to {id1: value1, id2: value2}
			};
			this.focusOnFirstField = props.focusOnFirstField;
			this.fieldRefMap = {};
		}

		setFieldValue(fieldId, fieldValue)
		{
			const fieldValues = this.state.fieldValues;
			fieldValues[fieldId] = fieldValue;

			this.setState({ fieldValues });
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
			/** @type {BaseField[]} */
			const fields = this.props.fields.map((field) => ((field.type === CombinedType)
				? FieldFactory.create(CombinedType, {
					value: {
						[field.primaryField.id]: this.getFieldValue(field.primaryField.id),
						[field.secondaryField.id]: this.getFieldValue(field.secondaryField.id),
					},
					onChange: (value) => {
						this.onChangeValue(field.primaryField.id, value[field.primaryField.id]);
						this.onChangeValue(field.secondaryField.id, value[field.secondaryField.id]);
					},
					config: {
						primaryField: {
							...field.primaryField,
							readOnly: false,
							value: this.getFieldValue(field.primaryField.id),
						},
						secondaryField: {
							...field.secondaryField,
							readOnly: false,
							value: this.getFieldValue(field.secondaryField.id),
							onChange: this.onChangeValue.bind(this, field.secondaryField.id),
						},
					},
					ref: (ref) => this.processFieldRef(field.id, ref),
				})
				: FieldFactory.create(field.type, {
					...field,
					value: this.getFieldValue(field.id),
					readOnly: false,
					onChange: this.onChangeValue.bind(this, field.id),
					ref: (ref) => this.processFieldRef(field.id, ref),
				})));

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
			if (this.props.fields.length > 0 && this.props.fields[0].id === fieldId && this.focusOnFirstField)
			{
				this.focusOnFirstField = false;
				setTimeout(
					() => this.fieldRefMap[fieldId].focus(),
					BX.prop.getNumber(this.props, 'fieldFocusDelay', 100),
				);
			}
		}

		/**
		 * @param {String} fieldId
		 * @returns {*|BaseField}
		 */
		getFieldRef(fieldId)
		{
			return this.fieldRefMap.hasOwnProperty(fieldId) ? this.fieldRefMap[fieldId] : null;
		}

		getFieldValue(fieldId)
		{
			let value = null;
			const field = this.props.fields.find((item) => item.id === fieldId);

			if (this.state.fieldValues.hasOwnProperty(fieldId))
			{
				value = this.state.fieldValues[fieldId];
			}
			else if (field && typeof field.value !== 'undefined')
			{
				value = field.value;
			}

			return value;
		}

		validate()
		{
			let validationResult = true;
			Object.values(this.fieldRefMap).forEach((field) => (validationResult &= field.validate()));

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

	/**
	 * @class FieldEditorStep
	 */
	class FieldEditorStep extends WizardStep
	{
		constructor()
		{
			super();
			this.fields = [];
			/** @var {FieldsLayout} */
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
				...extraProps,
			});
		}

		addCombinedField(primaryField, secondaryField)
		{
			this.fields.push({
				type: CombinedType,
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
					ref: (ref) => {
						this.editorRef = ref;
					},
				}),
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
			const field = this.fields.find((item) => item.id === fieldId);
			if (field)
			{
				field.value = fieldValue;
			}
		}

		onMoveToNextStep()
		{
			return (this.editorRef && this.editorRef.validate())
				? Promise.resolve()
				: Promise.reject();
		}
	}

	module.exports = { FieldEditorStep };
});
