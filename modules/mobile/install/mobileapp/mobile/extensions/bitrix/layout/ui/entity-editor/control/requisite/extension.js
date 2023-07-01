/**
 * @module layout/ui/entity-editor/control/requisite
 */
jn.define('layout/ui/entity-editor/control/requisite', (require, exports, module) => {

	const { EntityEditorField } = require('layout/ui/entity-editor/control/field');

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

	module.exports = { RequisiteField };
});
