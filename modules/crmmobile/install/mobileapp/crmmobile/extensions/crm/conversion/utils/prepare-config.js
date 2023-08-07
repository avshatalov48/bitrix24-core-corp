/**
 * @module crm/conversion/utils/prepare-config
 */
jn.define('crm/conversion/utils/prepare-config', (require, exports, module) => {
	const { Type, TypeId } = require('crm/type');
	const { merge, isEmpty } = require('utils/object');

	const resolveEntityTypeName = (entityTypeId) => Type.resolveNameById(entityTypeId).toLowerCase();

	/**
	 * @function createConversionConfig
	 *
	 * @param {Object} params
	 * @param {Array} params.entityTypeIds
	 * @param {number} params.categoryId
	 * @param {Object} params.requiredConfig
	 * @param {Object} params.additionalEntityConfig
	 *
	 * @returns {Object}
	 */
	const createConversionConfig = (params) => {
		const { entityTypeIds, categoryId, requiredConfig, additionalEntityConfig } = params;

		if (!isEmpty(requiredConfig))
		{
			return prepareRequiredConfig(params);
		}

		const conversionConfig = {};

		if (Array.isArray(entityTypeIds) && entityTypeIds.length > 0)
		{
			entityTypeIds.forEach((entityTypeId) => {
				const entityTypeName = resolveEntityTypeName(entityTypeId);
				const config = {
					active: 'Y',
					enableSync: 'N',
					entityTypeId,
				};

				if (entityTypeId === TypeId.Deal && categoryId)
				{
					config.initData = { categoryId };
				}

				if (additionalEntityConfig && additionalEntityConfig[entityTypeId])
				{
					merge(config, additionalEntityConfig[entityTypeId]);
				}

				conversionConfig[entityTypeName] = config;
			});

			return conversionConfig;
		}

		return conversionConfig;
	};

	const prepareRequiredConfig = ({ entityTypeIds, requiredConfig }) => {
		const conversionConfig = {};

		Object.keys(requiredConfig).forEach((key) => {
			const config = requiredConfig[key];
			const entityTypeId = Number(config.entityTypeId);
			const entityTypeName = resolveEntityTypeName(entityTypeId);
			config.entityTypeId = entityTypeId;
			config.enableSync = entityTypeIds.includes(entityTypeId) ? 'Y' : 'N';

			conversionConfig[entityTypeName] = config;
		});

		return conversionConfig;
	};

	module.exports = { createConversionConfig };
});
