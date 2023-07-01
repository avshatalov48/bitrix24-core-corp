/**
 * @module crm/ajax/category
 */
jn.define('crm/ajax/category', (require, exports, module) => {
	const { BaseAjax } = require('crm/ajax/base');

	const CategoryActions = {
		CREATE: 'create',
		UPDATE: 'update',
		DELETE: 'delete',
	};

	/**
	 * @class CategoryAjax
	 */
	class CategoryAjax extends BaseAjax
	{
		getEndpoint()
		{
			return 'crmmobile.Category';
		}

		/**
		 * @param {Number} entityTypeId
		 * @param {Object} fields
		 * @return {Promise<Object, void>}
		 */
		create(entityTypeId, fields)
		{
			return this.fetch(CategoryActions.CREATE, {
				entityTypeId,
				fields,
			});
		}

		/**
		 * @param {Number} entityTypeId
		 * @param {Number} categoryId
		 * @param {Object} fields
		 * @return {Promise<Object, void>}
		 */
		update(entityTypeId, categoryId, fields)
		{
			return this.fetch(CategoryActions.UPDATE, {
				entityTypeId,
				categoryId,
				fields,
			});
		}

		/**
		 * @param {Number} entityTypeId
		 * @param {Number} categoryId
		 * @return {Promise<Object, void>}
		 */
		delete(entityTypeId, categoryId)
		{
			return this.fetch(CategoryActions.DELETE, {
				entityTypeId,
				categoryId,
			});
		}
	}

	module.exports = {
		CategoryAjax: new CategoryAjax(),
		CategoryActions,
	};
});
