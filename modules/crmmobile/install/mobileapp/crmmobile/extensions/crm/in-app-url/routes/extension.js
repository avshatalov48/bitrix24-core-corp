/**
 * @module crm/in-app-url/routes
 */
jn.define('crm/in-app-url/routes', (require, exports, module) => {
	const { openEntityDetail, openEntityList } = require('crm/in-app-url/routes/open-actions');
	const { Type } = require('crm/type');

	/**
	 * @param {InAppUrl} inAppUrl
	 */
	module.exports = (inAppUrl) => {
		inAppUrl.register('/crm/:entityTypeName/details/:id/', ({ id, entityTypeName }, { context, queryParams }) => {
			const entityTypeId = Type.resolveIdByName(entityTypeName);

			if (!Type.isEntitySupportedById(entityTypeId))
			{
				return;
			}

			openEntityDetail(
				entityTypeId,
				id,
				{ ...context, queryParams },
			);
		}).name('crm:detailStaticEntityTypes');

		inAppUrl.register('/crm/type/:entityTypeId/details/:id/', ({ id, entityTypeId }, { context, queryParams }) => {
			if (!Type.isEntitySupportedById(entityTypeId))
			{
				return;
			}

			openEntityDetail(
				entityTypeId,
				id,
				{ ...context, queryParams },
			);
		}).name('crm:detailDynamicEntityTypes');

		inAppUrl.register('/crm/:typeName/:typeId/', ({ typeName, typeId }) => {
			const entityTypeId = typeName === 'type' ? typeId : Type.resolveIdByName(typeName);

			if (!Type.isEntitySupportedById(entityTypeId))
			{
				return;
			}

			const entityTypeName = Type.resolveNameById(entityTypeId);

			openEntityList({ activeTabName: entityTypeName });
		}).name('crm:entityList');
	};
});
