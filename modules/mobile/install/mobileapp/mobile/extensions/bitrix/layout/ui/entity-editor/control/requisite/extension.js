(() => {

	/**
	 * @class RequisiteField
	 */
	class RequisiteField extends EntityEditorField
	{
		constructor(props)
		{
			super(props);
		}

		getValueFromModel(defaultValue = [])
		{
			if (this.model)
			{
				const requisites = this.model.getField('REQUISITES_RAW', []);

				return Array.isArray(requisites) ? requisites : [];
			}

			return defaultValue;
		}

		getValuesToSave()
		{
			return {};
		}
	}

	jnexport(RequisiteField);
})();
