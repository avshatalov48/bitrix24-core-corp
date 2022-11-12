(() => {
	/**
	 * @class EntityEditor
	 */
	class EntityEditor extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			/** @type {EntityEditorColumn[]} */
			this.controls = [];
			/** @type {EntityEditorBaseController[]} */
			this.controllers = [];

			this.init(props);
		}

		componentWillReceiveProps(props)
		{
			this.init(props);
		}

		init(props)
		{
			this.id = CommonUtils.isNotEmptyString(props.id) ? props.id : CommonUtils.getRandom(4);
			this.settings = props.settings ? props.settings : {};

			/** @type {EntityModel} */
			this.model = BX.prop.get(this.settings, "model", null);
			/** @type {EntityScheme} */
			this.scheme = BX.prop.get(this.settings, "scheme", null);
			/** @type {EntityConfig} */
			this.config = BX.prop.get(this.settings, "config", null);

			this.entityTypeName = BX.prop.getString(this.settings, "entityTypeName", '');
			this.entityId = BX.prop.getInteger(this.settings, "entityId", 0);
			this.context = BX.prop.getObject(this.settings, "context", {});
			this.contextId = BX.prop.getString(this.settings, "contextId", "");
			this.serviceUrl = BX.prop.getString(this.settings, "serviceUrl", "");
			this.moduleId = BX.prop.getString(this.settings, "moduleId", '');

			this.isNew = this.entityId <= 0 && this.model.isIdentifiable();

			this.readOnly = BX.prop.getBoolean(this.settings, "readOnly", false);
			if (this.readOnly)
			{
				this.enableSectionEdit = this.enableSectionCreation = false;
			}

			this.availableSchemeElements = this.scheme.getAvailableElements();
			this.desktopUrl = BX.prop.getString(this.settings, "desktopUrl", '');
			this.controllers.forEach((controller) => controller.setModel(this.model));

			if (BX.prop.getBoolean(this.settings, "loadFromModel", false))
			{
				this.controllers.forEach((controller) => controller.loadFromModel());
			}
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column'
					}
				},
				...this.renderControls()
			)
		}

		getValuesFromModel()
		{
			if (this.model)
			{
				return this.model.getFields();
			}

			return {};
		}

		getValuesToSave()
		{
			let controlValues = {};

			[...this.controls, ...this.controllers].forEach((control) => {
				controlValues = {...controlValues, ...control.getValuesToSave()};
			});

			const loading = Object.keys(controlValues)
				.filter((name) => controlValues[name] instanceof Promise)
				.map((name) => controlValues[name])
			;

			return Promise
				.all(loading)
				.then((processedFields) => {
					processedFields.forEach((field) => {
						controlValues = {...controlValues, ...field};
					});

					return Promise.resolve(controlValues);
				})
				;
		}

		validate()
		{
			const validator = EntityAsyncValidator.create();
			this.controls.forEach((control) => {
				validator.addResult(control.validate());
			});

			return validator.validate();
		}

		renderControls()
		{
			return (
				this.scheme.getElements()
					.map((element, index) => {
						return this.renderControl(
							ref => {
								this.controls[index] = ref;
							},
							element.getType(),
							element.getName(),
							{
								schemeElement: element,
								readOnly: this.readOnly
							}
						)
					})
					.filter((element) => element)
			);
		}

		initializeControllers()
		{
			const controllers = BX.prop.getArray(this.settings, 'controllers', [])
				.map(controller => this.createController(
					BX.prop.getString(controller, 'name', ''),
					BX.prop.getString(controller, 'type', ''),
					BX.prop.getObject(controller, 'config', {})
				))
				.filter((controller) => controller)
			;
			controllers.map((controller) => controller.loadFromModel());

			return controllers;
		}

		getId()
		{
			return this.id;
		}

		getEntityId()
		{
			return this.entityId;
		}

		getControls()
		{
			return this.controls;
		}

		getControlById(controlId)
		{
			this.controls.find(control => control.getId() === controlId);
		}

		getControlByIdRecursive(controlId, controls = null)
		{
			if (!controls)
			{
				controls = this.getControls();
			}

			return controls.reduce((result, control) => {
				if (result)
				{
					return result;
				}

				if (!control instanceof EntityEditorBaseControl)
				{
					return result;
				}

				if (control && control.getId() === controlId)
				{
					return control;
				}
				else if (
					control instanceof EntityEditorColumn
					|| control instanceof EntityEditorSection
				)
				{
					const found = this.getControlByIdRecursive(controlId, control.getControls());

					return found ? found : result;
				}

				return result;
			}, null);
		}

		getDesktopUrl()
		{
			return this.desktopUrl;
		}

		renderControl(ref, type, id, settings)
		{
			settings["serviceUrl"] = this.serviceUrl;
			settings["model"] = this.model;
			settings["editor"] = this;

			return EntityEditorControl({ref, type, id, settings});
		}

		createController(id, type, settings)
		{
			settings["model"] = this.model;
			settings["editor"] = this;

			return EntityEditorControllerFactory.create({type, id, settings});
		}

		canChangeScheme()
		{
			return this.config && this.config.isChangeable();
		}

		componentDidMount()
		{
			this.controllers = this.initializeControllers();
		}
	}

	jnexport(EntityEditor)
})();