/**
 * @module layout/ui/entity-editor/control/address
 */
jn.define('layout/ui/entity-editor/control/address', (require, exports, module) => {

	const { EntityEditorField } = require('layout/ui/entity-editor/control/field');
	const { AddressValueConverter } = require('layout/ui/fields/address');

	/**
	 * @class EntityEditorAddressField
	 */
	class EntityEditorAddressField extends EntityEditorField
	{
		getValuesToSave()
		{
			const valuesToSave = super.getValuesToSave();

			valuesToSave[this.getName() + '_manual_edit'] = 'Y';

			return valuesToSave;
		}

		prepareBeforeSaving(value)
		{
			if (this.isMultiple())
			{
				return value.map((item) => this.prepareSingleValueBeforeSaving(item.value));
			}
			else
			{
				return this.prepareSingleValueBeforeSaving(value);
			}
		}

		prepareSingleValueBeforeSaving(value)
		{
			const { text, coords, id, json } = AddressValueConverter.convertFromValue(value);

			if (id && json === null)
			{
				return `${id}_del`;
			}

			if (json)
			{
				return json;
			}

			/**
			 * Old format
			 */
			let result = `${text ? text : ''}`;

			if (Array.isArray(coords) && coords.length === 2)
			{
				result += `|${coords[0] ? coords[0] : ''};${coords[1] ? coords[1] : ''}`;
			}

			return result;
		}
	}

	module.exports = { EntityEditorAddressField };
});
