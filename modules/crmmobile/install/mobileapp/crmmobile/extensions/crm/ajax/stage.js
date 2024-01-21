/**
 * @module crm/ajax/stage
 */
jn.define('crm/ajax/stage', (require, exports, module) => {
	const { BaseAjax } = require('crm/ajax/base');

	const StageActions = {
		CREATE: 'create',
		UPDATE: 'update',
		DELETE: 'delete',
	};

	/**
	 * @class StageAjax
	 */
	class StageAjax extends BaseAjax
	{
		getEndpoint()
		{
			return 'crmmobile.Stage';
		}

		getTtl()
		{
			return 7 * 24 * 3600; // 7 days
		}

		/**
		 * @param {Number} entityTypeId
		 * @param {Number} categoryId
		 * @param {Object} fields
		 * @return {Promise<Object, void>}
		 */
		create(entityTypeId, categoryId, fields)
		{
			return this.fetch(StageActions.CREATE, {
				entityTypeId,
				categoryId,
				fields,
			});
		}

		/**
		 * @param {Number} entityTypeId
		 * @param {Object} fields
		 * @return {Promise<Object, void>}
		 */
		update(entityTypeId, fields)
		{
			return this.fetch(StageActions.UPDATE, {
				entityTypeId,
				fields,
			});
		}

		/**
		 * @param {Number} entityTypeId
		 * @param {String} statusId
		 * @return {Promise<Object, void>}
		 */
		delete(entityTypeId, statusId)
		{
			return this.fetch(StageActions.DELETE, {
				entityTypeId,
				statusId,
			});
		}
	}

	module.exports = {
		StageAjax: new StageAjax(),
		StageActions,
	};
});
