/**
 * @module crm/terminal/services/field-manager
 */
jn.define('crm/terminal/services/field-manager', (require, exports, module) => {
	const { FieldFactory } = require('layout/ui/fields');
	const { mergeImmutable } = require('utils/object');
	const { FIELD_NAMES, getTerminalField } = require('crm/terminal/services/field-manager/fields');

	/**
	 * @class FieldManagerService
	 */
	class FieldManagerService
	{
		/**
		 * @param {Object[]} fieldsData
		 * @param {Object} options
		 */
		constructor(fieldsData, options = {})
		{
			this.fieldsData = fieldsData;
			this.renderIfEmpty = BX.prop.getBoolean(options, 'renderIfEmpty', true);
		}

		/**
		 * @param {String} name
		 * @param {Object} data
		 */
		renderField(name, data = {})
		{
			const fieldData = this.getFieldData(name);
			if (!fieldData)
			{
				return null;
			}

			const field = FieldFactory.create(
				fieldData.type,
				mergeImmutable(
					FieldManagerService.prepareFieldData(fieldData),
					data,
				),
			);

			return field.isEmpty() && !this.renderIfEmpty ? null : field;
		}

		/**
		 * @param {String} fieldName
		 * @return {Object|null}
		 */
		getFieldData(fieldName)
		{
			const foundFieldData = this.fieldsData.find((field) => field.name === fieldName);

			return foundFieldData || null;
		}

		static prepareFieldsData(fields)
		{
			return fields.map((fieldData) => FieldManagerService.prepareFieldData(fieldData));
		}

		static prepareFieldData(fieldData)
		{
			const field = getTerminalField(fieldData.name);

			return field && typeof field.prepareData === 'function' ? field.prepareData(fieldData) : fieldData;
		}
	}

	module.exports = {
		FieldManagerService,
		...FIELD_NAMES,
	};
});
