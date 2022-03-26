(() =>
{
	/**
	 * @class EntityEditorBaseController
	 */
	class EntityEditorBaseController
	{
		constructor(props)
		{
			this.id = CommonUtils.isNotEmptyString(props.id) ? props.id : CommonUtils.getRandom(4);
			this.settings = props.settings ? props.settings : {};
			/** @type {EntityEditor} */
			this.editor = BX.prop.get(this.settings, "editor", null);
			/** @type {EntityModel} */
			this.model = BX.prop.get(this.settings, "model", null);

			this.initialize();
		}

		setModel(model)
		{
			this.model = model;
		}

		loadFromModel()
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

		initialize()
		{
		}

		getValuesToSave()
		{
			return {};
		}
	}

	jnexport(EntityEditorBaseController)
})();
