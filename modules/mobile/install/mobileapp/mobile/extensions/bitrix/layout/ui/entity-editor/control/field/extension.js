(() => {
	/**
	 * @class EntityEditorField
	 */
	class EntityEditorField extends EntityEditorBaseControl
	{
		constructor(props)
		{
			super(props);

			this.state = {
				value: this.getValueFromModel()
			};

			/** @type {Fields.BaseField} */
			this.fieldRef = null;

			this.onChangeState = this.onChangeState.bind(this);
			this.onFocusIn = this.onFocusIn.bind(this);
			this.onFocusOut = this.onFocusOut.bind(this);
		}

		initializeStateFromModel()
		{
			this.setState({
				value: this.getValueFromModel()
			});
		}

		render()
		{
			const content = FieldFactory.create(this.type, {
				ref: (ref) => this.fieldRef = ref,
				id: this.getId(),
				title: this.getTitle(),
				multiple: this.isMultiple(),
				value: this.state.value,
				placeholder: this.isNewEntity() && this.getCreationPlaceholder(),
				readOnly: this.readOnly,
				onChange: this.onChangeState,
				onFocusIn: this.onFocusIn,
				onFocusOut: this.onFocusOut,
				config: this.prepareConfig(),
				required: this.isRequired()
			});

			return View(
				{
					style: {
						flexDirection: 'column'
					}
				},
				content || this.renderDefaultContent()
			)
		}

		prepareConfig()
		{
			return this.schemeElement.getData();
		}

		onChangeState(value)
		{
			this.setValue(value);
		}

		onFocusIn()
		{
			this.emit('EntityEditorField::onFocusIn', [{
				editorId: this.editor.getId(),
				fieldName: this.getName()
			}]);
		}

		onFocusOut()
		{
			this.emit('EntityEditorField::onFocusOut', [{
				editorId: this.editor.getId(),
				fieldName: this.getName()
			}]);
		}

		renderDefaultContent()
		{
			return View(
				{},
				Text(
					{
						text: `Field ${String(this.type)} ${String(this.getTitle())} ${String(this.editor.state.value)}`
					}
				)
			)
		}

		isMultiple()
		{
			return this.schemeElement && this.schemeElement.isMultiple();
		}

		getTitle()
		{
			if (!this.schemeElement)
			{
				return "";
			}

			let title = this.schemeElement.getTitle();
			if (title === "")
			{
				title = this.schemeElement.getName();
			}

			return title;
		}

		getValueFromModel(defaultValue = '')
		{
			if (this.model)
			{
				return this.model.getField(this.getName(), defaultValue);
			}

			return defaultValue;
		}

		getValue()
		{
			return this.state.value;
		}

		getValuesToSave()
		{
			return {
				[this.getName()]: this.getValue()
			};
		}

		getName()
		{
			return this.schemeElement ? this.schemeElement.getName() : "";
		}

		validate()
		{
			if (!this.isEditable())
			{
				return true;
			}

			return this.fieldRef.validate();
		}

		isRequired()
		{
			return this.schemeElement && this.schemeElement.isRequired();
		}

		setValue(value)
		{
			return new Promise((resolve) => {
				this.setState({value}, () => {
					this.emit('EntityEditorField::onChangeState', [{
						editorId: this.editor.getId(),
						fieldName: this.getName(),
						fieldValue: value,
					}]);

					resolve();
				});
			})
		}
	}

	jnexport(EntityEditorField)
})();