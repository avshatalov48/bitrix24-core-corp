/**
 * @module crm/conversion/utils/prepare-fields
 */
jn.define('crm/conversion/utils/prepare-fields', (require, exports, module) => {
	const { Type } = require('crm/type');

	/**
	 * @function prepareConversionFields
	 * return array
	 */
	const prepareConversionFields = (requiredAction) => {
		const {
			CONFIG: config,
			FIELD_NAMES: fieldNames,
		} = requiredAction;
		const result = [];

		result.push({
			id: 'entities',
			type: 'boolean',
			sort: 10,
			data: Object.keys(config).map((key) => {
				const { active, entityTypeId, title, enableSync } = config[key];
				const entityTypeName = Type.resolveNameById(entityTypeId);

				return active === 'Y' && enableSync === 'Y'
					? {
						id: Number(entityTypeId),
						title,
						entityTypeName,
					}
					: null;
			}).filter(Boolean),
		}, {
			id: 'fields',
			sort: 20,
			type: 'string',
			data: fieldNames.map((field, index) => ({
				id: index,
				description: field,
			})),
		});

		return result;
	};

	module.exports = { prepareConversionFields };
});
