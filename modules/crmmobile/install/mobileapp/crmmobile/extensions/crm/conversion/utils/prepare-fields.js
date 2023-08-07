/**
 * @module crm/conversion/utils/prepare-fields
 */
jn.define('crm/conversion/utils/prepare-fields', (require, exports, module) => {
	const { Type } = require('crm/type');

	/**
	 * @function prepareConversionFields
	 * @param {Object} requiredAction
	 * @param {Object} requiredAction.CONFIG
	 * @param {Object} requiredAction.FIELD_NAMES
	 * @returns {Array<Object>}
	 */
	const prepareConversionFields = (requiredAction = {}) => {
		const {
			CONFIG: config,
			FIELD_NAMES: fieldNames,
		} = requiredAction;

		const entitiesData = Object.keys(config).map((key) => {
			const { active, entityTypeId, title, enableSync } = config[key];
			const entityTypeName = Type.resolveNameById(entityTypeId);

			return active === 'Y' && enableSync === 'Y'
				? {
					id: Number(entityTypeId),
					title,
					entityTypeName,
				}
				: null;
		}).filter(Boolean);

		return [
			{
				id: 'entities',
				type: 'boolean',
				sort: 10,
				data: entitiesData,
			},
			{
				id: 'fields',
				sort: 20,
				type: 'string',
				data: fieldNames.map((field, index) => ({
					id: index,
					description: field,
				})),
			},
		];
	};

	module.exports = { prepareConversionFields };
});
