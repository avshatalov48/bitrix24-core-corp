(() => {
	/**
	 * @class EntityEditorBaseControl
	 */
	class EntityEditorBaseControl extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.initialize(props.id, props.settings, props.type);
		}

		componentWillReceiveProps(props)
		{
			this.initialize(props.id, props.settings, props.type);

			if (this.editor && this.editor.settings.loadFromModel)
			{
				this.initializeStateFromModel();
			}
		}

		initialize(id, settings, type)
		{
			this.id = CommonUtils.isNotEmptyString(id) ? id : CommonUtils.getRandom(4);
			this.settings = settings ? settings : {};

			/** @type {EntityEditor} */
			this.editor = BX.prop.get(this.settings, "editor", null);
			/** @type {EntityModel} */
			this.model = BX.prop.get(this.settings, "model", null);
			/** @type {EntitySchemeElement} */
			this.schemeElement = BX.prop.get(this.settings, "schemeElement", null);

			this.readOnly = BX.prop.getBoolean(this.settings, "readOnly", true);
			this.type = type || '';
		}

		initializeStateFromModel()
		{

		}

		on(eventName, callback)
		{
			BX.addCustomEvent(eventName, callback);

			return this;
		}

		emit(eventName, args)
		{
			BX.postComponentEvent(eventName, args);
		}

		renderFromModel(ref)
		{
			const elements = this.schemeElement ? this.schemeElement.getElements() : [];

			return (
				elements
					.map((element, index) => {
						return this.editor.renderControl(
							controlRef => ref(controlRef, index),
							element.getType(),
							element.getName(),
							{
								schemeElement: element,
								readOnly: this.readOnly,
								model: this.model
							}
						)
					})
					.filter((element) => element)
			);
		}

		getId()
		{
			return this.id;
		}

		/**
		 * Method 'getControls' must be implemented in ancestors.
		 *
		 * @returns {EntityEditorBaseControl[]}
		 */
		getControls()
		{
			return [];
		}

		getValuesToSave()
		{
			let fieldValues = {};

			this.getControls().forEach((field) => {
				fieldValues = {
					...fieldValues,
					...field.getValuesToSave()
				};
			});

			return fieldValues;
		}

		isEditable()
		{
			return this.schemeElement && this.schemeElement.isEditable();
		}

		validate()
		{
			return true;
		}

		isNewEntity()
		{
			return this.editor && this.editor.isNew;
		}

		getCreationPlaceholder()
		{
			return this.schemeElement && this.schemeElement.getCreationPlaceholder();
		}
	}

	jnexport(EntityEditorBaseControl)
})();