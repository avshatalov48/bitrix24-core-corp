/**
 * @module crm/conversion/utils/prepare-config
 */
jn.define('crm/conversion/utils/prepare-config', (require, exports, module) => {
	const { Type, TypeId } = require('crm/type');
	const { merge } = require('utils/object');

	/**
	 * @function prepareConversionConfig
	 * return object
	 */
	const prepareConversionConfig = ({ entities, categoryId, requiredConfig, additionalEntityConfig }) => {
		const conversionConfig = {};
		const resolveEntityTypeName = (entityTypeId) => Type.resolveNameById(entityTypeId).toLowerCase();
		if (requiredConfig)
		{
			Object.keys(requiredConfig).forEach((key) => {
				const config = requiredConfig[key];
				const entityTypeId = Number(config.entityTypeId);
				const entityTypeName = resolveEntityTypeName(entityTypeId);
				config.entityTypeId = entityTypeId;
				config.enableSync = entities.includes(entityTypeId) ? 'Y' : 'N';

				conversionConfig[entityTypeName] = config;
			});

			return conversionConfig;
		}

		if (Array.isArray(entities) && entities.length > 0)
		{
			entities.forEach((entityTypeId) => {
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

	module.exports = { prepareConversionConfig };
});
