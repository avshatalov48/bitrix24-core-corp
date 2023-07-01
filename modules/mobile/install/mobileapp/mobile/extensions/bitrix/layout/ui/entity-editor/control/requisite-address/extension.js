/**
 * @module layout/ui/entity-editor/control/requisite-address
 */
jn.define('layout/ui/entity-editor/control/requisite-address', (require, exports, module) => {

	const { EntityEditorField } = require('layout/ui/entity-editor/control/field');

	/**
	 * @class RequisiteAddressField
	 */
	class RequisiteAddressField extends EntityEditorField
	{
		constructor(props)
		{
			super(props);
		}

		getValueFromModel(defaultValue = [])
		{
			if (this.model)
			{
				const requisites = this.model.getField('REQUISITES_ADDRESSES_RAW', {});

				return Object.keys(requisites).map((id) => ({id, address: requisites[id]}));
			}

			return defaultValue;
		}

		getValuesToSave()
		{
			return {};
		}
	}

	module.exports = { RequisiteAddressField };
});

