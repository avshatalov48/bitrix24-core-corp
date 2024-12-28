/**
 * @module crm/entity-detail/component/analytics-provider
 */
jn.define('crm/entity-detail/component/analytics-provider', (require, exports, module) => {
	/**
	 * @param {Object} entityModel
	 */
	const analyticsProvider = (entityModel) => {
		const { ENTITY_TYPE_ID: entityTypeId } = entityModel || {};

		return {
			module: 'crm',
			entityTypeId,
			analyticsSection: 'crm',
		};
	};

	module.exports = { analyticsProvider };
});
